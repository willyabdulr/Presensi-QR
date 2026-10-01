#!/bin/sh

# 1. Beri izin akses folder storage & cache (mencegah Permission Denied)
chmod -R 777 storage bootstrap/cache

# 2. Clear cache sebelum migrasi
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# 3. Jalankan migrasi & seeder secara otomatis
echo "==> Menjalankan migrasi database..."
php artisan migrate --force || true

echo "==> Menjalankan seeder database..."
php artisan db:seed --force || true

# 4. Cache ulang konfigurasi untuk performa production
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# 5. Konfigurasi Port Nginx
PORT="${PORT:-8080}"
echo "==> Menyiapkan port Nginx (PORT: ${PORT})..."
if [ "$PORT" != "8080" ]; then
    echo "==> Menambahkan listen port ${PORT} ke Nginx..."
    sed -i "s/listen 8080 default_server;/listen 8080;\n    listen ${PORT} default_server;/" /etc/nginx/http.d/default.conf
fi

echo "==> Memeriksa sintaks Nginx..."
nginx -t

echo "==> Menjalankan PHP-FPM di background..."
php-fpm &

sleep 1

echo "==> Menjalankan Nginx di foreground..."
exec nginx -g 'daemon off;'