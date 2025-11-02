FROM php:8.3-apache
WORKDIR /var/www/html
COPY backend/ /var/www/html/
EXPOSE 80
CMD ["apache2-foreground"]