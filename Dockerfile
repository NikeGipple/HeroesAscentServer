FROM php:8.3-apache

WORKDIR /var/www/html

# Abilita mod_rewrite e consenti .htaccess
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Installa estensioni PHP, Node, npm, utilità e Certbot
RUN apt-get update && apt-get install -y \
    nano \
    cron \
    nodejs \
    npm \
    libpng-dev libjpeg-dev libfreetype6-dev zip git unzip curl \
    certbot python3-certbot-apache \
    && docker-php-ext-install pdo pdo_mysql gd \
    && rm -rf /var/lib/apt/lists/*

# Installa Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copia progetto Laravel
COPY . /var/www/html/

# Installa dipendenze Laravel
RUN composer install --no-dev --optimize-autoloader

# === Installa e builda il frontend React/Vite ===
WORKDIR /var/www/html

# Installa le dipendenze Node e builda il frontend
RUN npm install 

# === Permessi e configurazione Apache ===
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Imposta Apache per ascoltare anche su 10000
RUN sed -i 's/^Listen 80$/Listen 80\nListen 10000/' /etc/apache2/ports.conf

# Crea un VirtualHost per Laravel
RUN echo '<VirtualHost *:80>\n\
    ServerName heroesascent.org\n\
    ServerAlias www.heroesascent.org\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>\n\
\n\
<VirtualHost *:10000>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Cron per rinnovo automatico SSL ogni 12 ore
RUN echo '0 */12 * * * root certbot renew --quiet && service apache2 reload' >> /etc/crontab

EXPOSE 80 10000

# Avvia cron e Apache insieme
CMD service cron start && apache2-foreground
