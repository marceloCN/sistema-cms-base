# Imagen base: PHP 8.2 + Apache (desarrollo y pruebas locales).
# En producción se recomienda nginx + php-fpm; este Dockerfile es para
# levantar el proyecto rápidamente con docker-compose.
FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install zip mysqli pdo pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# rewrite (equivale al .htaccess) + compresión + caché de estáticos
RUN a2enmod rewrite deflate expires headers

# VirtualHost: docroot = /var/www/html/public
RUN printf '%s\n' \
    '<VirtualHost *:80>' \
    '    ServerName localhost' \
    '    DocumentRoot /var/www/html/public' \
    '    ErrorLog ${APACHE_LOG_DIR}/cms-error.log' \
    '    CustomLog ${APACHE_LOG_DIR}/cms-access.log combined' \
    '' \
    '    <Directory /var/www/html/public>' \
    '        Options -Indexes +FollowSymLinks' \
    '        AllowOverride All' \
    '        Require all granted' \
    '    </Directory>' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf

COPY php.ini /usr/local/etc/php/php.ini

EXPOSE 80