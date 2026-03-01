# ---------- STAGE 1: Node build ----------
FROM node:20-alpine AS nodebuilder

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build


# ---------- STAGE 2: PHP ----------
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Copiamos assets compilados desde Node
COPY --from=nodebuilder /app/public/build /var/www/public/build

RUN composer install --no-interaction --optimize-autoloader

RUN chown -R www-data:www-data /var/www

EXPOSE 9000

CMD ["php-fpm"]