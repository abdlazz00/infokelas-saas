#!/bin/bash

set -e

echo "🛠️  Running post-deploy setup..."

# 1. Pastikan folder storage ADA sebelum di-setting permission
# Ini wajib karena seringkali folder kosong tidak ter-upload ke git
mkdir -p storage/logs
mkdir -p storage/app/public
mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p bootstrap/cache

# 2. Fix Permission (Lakukan DULUAN sebelum artisan command)
# Kita buka akses lebar dulu (777) ke folder storage untuk memastikan tidak ada permission denied
chmod -R 777 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "📂 Permissions fixed."

# 3. Jalankan Package Discovery & Upgrade
php artisan package:discover --ansi
php artisan filament:upgrade

# 4. Caching Configuration
echo "🔥 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Link Storage
if [ ! -L public/storage ]; then
    echo "🔗 Linking storage..."
    php artisan storage:link
fi

echo "🚀 Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
