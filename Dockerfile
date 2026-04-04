FROM php:8.4-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev \
    libxml2-dev libzip-dev libicu-dev && \
    docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath gd intl

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Clonar el repo privado con token
RUN git clone https://xxxxxxxxxxx@github.com/armando19993/api-ecommerce-grancamerino.git /var/www/html

WORKDIR /var/www/html

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader

# Permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Configurar Apache para Laravel
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite

# Post-deploy
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

CMD ["apache2-foreground"]