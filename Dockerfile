# -------------------------------------------------
# Stage 1 - Composer
# -------------------------------------------------
FROM docker.io/library/composer:latest AS composer

# -------------------------------------------------
# Stage 2 - PHP
# -------------------------------------------------
FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get clean && rm -rf /var/lib/apt/lists/* && \
    for i in 1 2 3; do \
        apt-get update && \
        apt-get install -y --no-install-recommends --fix-missing \
            git \
            curl \
            libpng-dev \
            libonig-dev \
            libxml2-dev \
            libzip-dev \
            zip \
            unzip \
            nginx \
            supervisor \
            ca-certificates \
        && break || sleep 5; \
    done && \
    rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Copiar Composer desde el stage anterior
COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction || \
    composer install --optimize-autoloader --no-interaction

RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache

RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

RUN rm /etc/nginx/sites-enabled/default

COPY nginx/laravel.conf /etc/nginx/sites-available/laravel
RUN ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/

COPY supervisor/supervisord.conf \
    /etc/supervisor/conf.d/supervisord.conf

RUN php artisan config:cache || true && \
    php artisan route:cache || true && \
    php artisan view:cache || true

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
