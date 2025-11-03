FROM php:8.3-apache

WORKDIR /var/www/html

# Copia il progetto Laravel
COPY . /var/www/html/

# Abilita mod_rewrite e consenti l'uso di .htaccess
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Imposta DocumentRoot sulla cartella public
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf

# Installa estensioni PHP e utilità (incluso nano)
RUN apt-get update && apt-get install -y \
    nano \
    libpng-dev libjpeg-dev libfreetype6-dev zip git unzip curl \
    && docker-php-ext-install pdo pdo_mysql gd

# Installa Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Installa le dipendenze Laravel
RUN composer install --no-dev --optimize-autoloader

# Imposta i permessi corretti
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Imposta Apache sulla porta 10000
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf

EXPOSE 10000
CMD ["apache2-foreground"]

# Imposta il DocumentRoot corretto (quello dentro il container)
# Imposta il DocumentRoot corretto (con porta 10000)
RUN echo '<VirtualHost *:10000>\n\
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

