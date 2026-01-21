#!/bin/bash

# Hentikan script jika ada error (kecuali saat menunggu DB)
set -e

echo "🛠️  Running post-deploy setup..."

# 1. Pastikan folder storage & cache ada
mkdir -p storage/logs
mkdir -p storage/app/public
mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p bootstrap/cache

# 2. Fix Permission (Wajib agar tidak Error 500)
chmod -R 777 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 3. AUTOMATIC MIGRATION
echo "📦 Migrating database..."
php artisan migrate --force

echo "🌱 Seeding database..."
php artisan db:seed --force

# 5. Jalankan Setup Laravel Lainnya
php artisan package:discover --ansi
php artisan filament:upgrade
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Link Storage
if [ ! -L public/storage ]; then
    echo "🔗 Linking storage..."
    php artisan storage:link
fi

echo "🚀 Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
