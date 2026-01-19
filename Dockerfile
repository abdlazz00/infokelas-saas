# Stage 1: Build Frontend
FROM node:20-alpine as frontend
WORKDIR /app
COPY package*.json vite.config.js ./
RUN npm install
COPY resources ./resources
COPY public ./public
RUN npm run build

# Stage 2: Build Backend
FROM composer:2 as composer_build
WORKDIR /app
COPY composer.json composer.lock ./
# Tetap gunakan --no-scripts
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-scripts

# Stage 3: Production Image
FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# 1. Install Dependencies Sistem
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    curl \
    bash \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers

# 2. Install PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd intl zip opcache

# 3. Copy Config (Langsung set permission executable untuk entrypoint)
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/start-container

# 4. Copy Application Files dengan Owner www-data (SOLUSI STUCK)
# Kita set owner langsung saat copy, jadi tidak perlu chown -R berat di akhir
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=frontend /app/public/build public/build
COPY --chown=www-data:www-data --from=composer_build /app/vendor vendor

# 5. Setup Permission Folder Khusus
# Kita hanya jalankan chmod pada folder yang butuh write access, jauh lebih ringan
RUN chmod -R 775 storage bootstrap/cache

# Expose port
EXPOSE 8080

# Entrypoint
ENTRYPOINT ["start-container"]
