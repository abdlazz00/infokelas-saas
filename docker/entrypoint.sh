#!/bin/bash

# Hentikan script jika ada error
set -e

echo "🛠️  Running post-deploy setup..."

# 1. Jalankan Package Discovery (PENTING: karena kita skip di build tadi)
php artisan package:discover --ansi

# 2. Jalankan Filament Upgrade (untuk publish assets filament)
php artisan filament:upgrade

# 3. Caching Configuration
echo "🔥 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 4. Link Storage
if [ ! -L public/storage ]; then
    echo "🔗 Linking storage..."
    php artisan storage:link
fi

# 5. FIX PERMISSION (PENTING!)
# Karena perintah di atas dijalankan sebagai root, file cache jadi milik root.
# Kita harus kembalikan kepemilikannya ke www-data agar Nginx tidak Error 500.
echo "👮 Fixing permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

echo "🚀 Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
