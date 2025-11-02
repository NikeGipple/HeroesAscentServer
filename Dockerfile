# Usa l'immagine ufficiale PHP con Apache
FROM php:8.3-apache

# Imposta la cartella di lavoro
WORKDIR /var/www/html

# Copia tutto il contenuto del progetto nella root del container
COPY . /var/www/html/

# Abilita mod_rewrite per Laravel
RUN a2enmod rewrite

# Permetti l'uso di .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Installa estensioni PHP necessarie per Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev zip git unzip curl \
    && docker-php-ext-install pdo pdo_mysql gd

# Installa Composer (gestore pacchetti PHP)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Installa le dipendenze Laravel
RUN composer install --no-dev --optimize-autoloader

# Imposta i permessi per storage e cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Espone la porta per Apache
EXPOSE 10000

# Avvia Apache su porta 10000 (o quella definita in $PORT)
CMD ["bash", "-c", "sed -i 's/Listen 80/Listen ${PORT:-10000}/' /etc/apache2/ports.conf && apache2-foreground"]
