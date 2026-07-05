#!/bin/sh
set -e

# ── Wait for MySQL ────────────────────────────────────────────
echo "Menunggu koneksi ke MySQL di ${DB_HOST}:${DB_PORT}..."
while ! nc -z "${DB_HOST}" "${DB_PORT}"; do
  echo "MySQL belum siap, menunggu..."
  sleep 5
done
echo "✅ MySQL terkoneksi, lanjut migrasi..."

# ── Laravel bootstrap ─────────────────────────────────────────
php artisan migrate --force || { echo "❌ Migrasi gagal!"; exit 1; }
php artisan db:seed --force || { echo "❌ Seeder gagal!"; exit 1; }

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force

# Ensure nginx log dir exists
mkdir -p /var/log/nginx /var/log/supervisor

echo "🚀 Menjalankan nginx + PHP-FPM via supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
