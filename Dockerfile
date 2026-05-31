FROM php:8.2-apache

# 1. Instalar extensiones necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 2. CORRECCIÓN AH00534: Desactivar el modo "evento" y activar "prefork" (obligatorio para PHP)
RUN a2dismod mpm_event
RUN a2enmod mpm_prefork
RUN a2enmod rewrite

# 3. Copiar archivos
COPY . /var/www/html/

# 4. Crear carpetas de uploads (que Git ignoró) y dar permisos
# (Incluimos esto de nuevo para asegurar que siempre se creen)
RUN mkdir -p /var/www/html/assets/uploads/avatars \
    && mkdir -p /var/www/html/assets/uploads/posts \
    && mkdir -p /var/www/html/assets/uploads/chats/private \
    && mkdir -p /var/www/html/assets/uploads/chats/rooms \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/assets/uploads

EXPOSE 80
CMD ["apache2-foreground"]