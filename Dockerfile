FROM php:8.2-apache

# 1. Instalar extensiones necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

# 2. Configurar Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html

# 3. Copiar todo el código desde GitHub
COPY . /var/www/html/

# 4. Crear las carpetas de uploads que .gitignore ignoró
RUN mkdir -p /var/www/html/assets/uploads/avatars \
    && mkdir -p /var/www/html/assets/uploads/posts \
    && mkdir -p /var/www/html/assets/uploads/chats/private \
    && mkdir -p /var/www/html/assets/uploads/chats/rooms

# 5. Dar permisos (ahora sí funcionará porque las carpetas ya existen)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/assets/uploads

# 6. Puerto y comando de inicio
EXPOSE 80
CMD ["apache2-foreground"]