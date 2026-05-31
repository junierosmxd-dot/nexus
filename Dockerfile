FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

# Configurar Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Copiar todo el código desde GitHub
COPY . /var/www/html/

# --- IMPORTANTE: Crear las carpetas que Git ignoró ---
RUN mkdir -p /var/www/html/assets/uploads/avatars && \
    mkdir -p /var/www/html/assets/uploads/posts && \
    mkdir -p /var/www/html/assets/uploads/chats/private && \
    mkdir -p /var/www/html/assets/uploads/chats/rooms

# Dar permisos a Apache
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 777 /var/www/html/assets/uploads

# Puerto
EXPOSE 80

CMD ["apache2-foreground"]