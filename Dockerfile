FROM php:8.2-apache

# 1. Instalar extensiones de PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 2. Arreglo de Apache (Evita el error MPM)
RUN a2dismod mpm_event && \
    a2enmod mpm_prefork && \
    a2enmod rewrite

# 3. Copiar todo el código desde GitHub
COPY . /var/www/html/

# 4. TRUCO: Crear la carpeta Y dar permisos en la misma instrucción
# Esto garantiza que la carpeta existe antes de intentar cambiar permisos
RUN mkdir -p /var/www/html/assets/uploads && \
    mkdir -p /var/www/html/assets/uploads/avatars && \
    mkdir -p /var/www/html/assets/uploads/posts && \
    mkdir -p /var/www/html/assets/uploads/chats/private && \
    mkdir -p /var/www/html/assets/uploads/chats/rooms && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 777 /var/www/html/assets/uploads

# 5. Iniciar Apache
CMD ["apache2-foreground"]