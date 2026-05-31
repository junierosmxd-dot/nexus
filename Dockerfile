FROM php:8.2-apache

# Habilitar mod_rewrite y mysqli
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

# Configurar Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Copiar todo el proyecto
COPY . /var/www/html/

# Permisos correctos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/assets/uploads

# Puerto
EXPOSE 80

CMD ["apache2-foreground"]