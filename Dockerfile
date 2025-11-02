FROM php:8.3-apache

WORKDIR /var/www/html
COPY backend/ /var/www/html/

RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

EXPOSE 10000
CMD ["bash", "-c", "sed -i 's/Listen 80/Listen ${PORT:-10000}/' /etc/apache2/ports.conf && apache2-foreground"]
