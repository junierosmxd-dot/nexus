FROM php:8.2-apache

# Instalar extensiones necesarias de PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

# Configurar la carpeta raíz de Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Copiar todos los archivos del proyecto al contenedor
COPY . /var/www/html/

# IMPORTANTE: Crear las carpetas que Git ignoró
RUN mkdir -p /var/www/html/assets/uploads/avatars \
    && mkdir -p /var/www/html/assets/uploads/posts \
    && mkdir -p /var/www/html/assets/uploads/chats/private \
    && mkdir -p /var/www/html/assets/uploads/chats/rooms

# Asignar permisos de escritura para Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/assets/uploads

# Puerto
EXPOSE 80

CMD ["apache2-foreground"]