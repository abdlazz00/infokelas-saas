#!/bin/bash

# Pastikan script berhenti jika ada error
set -e

# Caching config untuk performa production
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Jalankan storage link jika belum ada
if [ ! -L public/storage ]; then
    echo "Linking storage..."
    php artisan storage:link
fi

# Opsional: Jalankan migrasi database otomatis (Hati-hati di production!)
# Uncomment baris di bawah jika ingin migrasi jalan otomatis setiap deploy
# echo "Running migrations..."
# php artisan migrate --force

# Jalankan Supervisor
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
