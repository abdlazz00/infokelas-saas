# Stage 1: Build Frontend (Vite/Tailwind)
FROM node:20-alpine as frontend
WORKDIR /app
COPY package*.json vite.config.js ./
RUN npm install
COPY resources ./resources
COPY public ./public
RUN npm run build

# Stage 2: Build Backend (Composer)
FROM composer:2 as composer_build
WORKDIR /app
COPY composer.json composer.lock ./
# PERBAIKAN: Ditambahkan --no-scripts agar tidak error saat menjalankan artisan command
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-scripts

# Stage 3: Production Image
FROM php:8.2-fpm-alpine

# Setup working directory
WORKDIR /var/www/html

# 1. Update repository dan install dependencies sistem dasar
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    curl \
    bash \
    zip \
    unzip

# 2. Install library development untuk compile ekstensi PHP
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers

# 3. Configure & Install PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd intl zip opcache

# Copy konfigurasi custom
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/start-container

# Copy source code dan hasil build
COPY . .
COPY --from=frontend /app/public/build public/build
COPY --from=composer_build /app/vendor vendor

# Setup permissions
RUN chmod +x /usr/local/bin/start-container \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Expose port
EXPOSE 80

# Entrypoint
ENTRYPOINT ["start-container"]
