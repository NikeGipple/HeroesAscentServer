FROM php:8.3-apache

# Imposta la directory pubblica
WORKDIR /var/www/html

# Copia i file PHP del backend
COPY backend/ /var/www/html/

# Abilita mod_rewrite (opzionale ma utile)
RUN a2enmod rewrite

# Espone la porta usata da Render
EXPOSE 10000

# Adatta Apache alla porta dinamica assegnata da Render
CMD ["bash", "-c", "sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf && apache2-foreground"]
