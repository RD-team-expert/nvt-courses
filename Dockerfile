FROM php:8.2-apache

# Install system dependencies + intl extension
RUN apt-get update && apt-get install -y \
    git zip unzip \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libwebp-dev \
    libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql gd zip pcntl sockets intl \
    && rm -rf /var/lib/apt/lists/*

# Apache setup
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --no-scripts --no-autoloader

# Copy application code
COPY . .
RUN composer dump-autoload --optimize

# Create proper Laravel structure with correct permissions
RUN mkdir -p storage/logs storage/framework/{cache,sessions,testing,views} storage/app/public bootstrap/cache \
    && touch storage/logs/laravel.log \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
