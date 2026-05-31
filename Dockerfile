FROM php:8.2-apache

# Instalar extensiones de PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# --- CORRECCIÓN DE ERROR AH00534 ---
# Desactivar el modo conflictivo y activar el correcto para PHP
RUN a2dismod mpm_event && \
    a2enmod mpm_prefork && \
    a2enmod rewrite

# Configurar la carpeta raíz
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Copiar archivos
COPY . /var/www/html/

# Crear las carpetas de uploads (que Git ignora)
RUN mkdir -p /var/www/html/assets/uploads/avatars \
    && mkdir -p /var/www/html/assets/uploads/posts \
    && mkdir -p /var/www/html/assets/uploads/chats/private \
    && mkdir -p /var/www/html/assets/uploads/chats/rooms

# Permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/assets/uploads

EXPOSE 80
CMD ["apache2-foreground"]