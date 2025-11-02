FROM php:8.3-apache

WORKDIR /var/www/html
COPY . /var/www/html/

RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev zip git unzip curl \
    && docker-php-ext-install pdo pdo_mysql gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 10000

# 👇 Avvia Apache direttamente su porta 10000, senza usare sed
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf
CMD ["apache2-foreground"]
