#!/bin/bash
set -e

echo "🛠️  Running post-deploy setup..."

# 1. Buat folder log & cache manual (mencegah error 500 karena folder log tidak ada)
mkdir -p storage/logs
mkdir -p storage/app/public
mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p bootstrap/cache

# 2. Paksa permission folder agar bisa ditulis oleh Nginx/PHP
chmod -R 777 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 3. Jalankan command Laravel
php artisan package:discover --ansi
php artisan filament:upgrade
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Storage Link
if [ ! -L public/storage ]; then
    php artisan storage:link
fi

echo "🚀 Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
