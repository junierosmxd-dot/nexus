FROM php:8.2-apache

# 1. Instalar extensiones PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 2. SOLUCIÓN DEFINITIVA AH00534: Forzar MPM correcto para PHP
RUN a2dismod mpm_event mpm_worker \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# 3. Copiar código
COPY . /var/www/html/

# 4. Crear carpetas de uploads y asignar permisos (en una sola línea para evitar fallos)
RUN mkdir -p /var/www/html/assets/uploads/{avatars,posts,chats/private,chats/rooms} \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/assets/uploads

EXPOSE 80
CMD ["apache2-foreground"]