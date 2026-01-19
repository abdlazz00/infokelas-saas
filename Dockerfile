# Stage 1: Build Frontend (Vite/Tailwind)
FROM node:20-alpine as frontend
WORKDIR /app
COPY package*.json vite.config.js ./
RUN npm install
COPY resources ./resources
COPY public ./public
# Build assets ke folder public/build
RUN npm run build

# Stage 2: Build Backend (Composer)
FROM composer:2 as composer_build
WORKDIR /app
COPY composer.json composer.lock ./
# Install dependencies tanpa dev dependencies untuk production
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

# Stage 3: Production Image
FROM php:8.2-fpm-alpine

# Install system dependencies & PHP extensions yang dibutuhkan Laravel & Filament
# libpng, libjpeg, freetype, libzip, icu-dev (untuk intl) sangat penting untuk Filament/Intervention Image
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    bash \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip opcache

# Setup working directory
WORKDIR /var/www/html

# Copy konfigurasi custom (kita akan buat file ini di langkah selanjutnya)
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/start-container

# Copy file project
COPY . .

# Copy assets dari stage frontend
COPY --from=frontend /app/public/build public/build

# Copy vendor dari stage composer
COPY --from=composer_build /app/vendor vendor

# Setup permissions
RUN chmod +x /usr/local/bin/start-container \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["start-container"]
