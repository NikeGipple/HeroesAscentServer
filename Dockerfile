FROM php:8.3-apache

WORKDIR /var/www/html

# Copia tutto il codice Laravel
COPY . /var/www/html/

# Abilita mod_rewrite per Laravel
RUN a2enmod rewrite

# Permetti l'uso di .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Imposta la DocumentRoot corretta (cartella public)
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf

# Installa estensioni PHP necessarie per Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev zip git unzip curl \
    && docker-php-ext-install pdo pdo_mysql gd

# Installa Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Installa le dipendenze Laravel
RUN composer install --no-dev --optimize-autoloader

# Imposta i permessi per storage e cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Espone la porta di Apache
EXPOSE 10000

# Avvia Apache
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf
CMD ["apache2-foreground"]
