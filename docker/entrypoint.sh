#!/bin/sh
set -e

cd /var/www/html

echo "▶ Linking storage..."
php artisan storage:link --force 2>/dev/null || true

# Genera las variantes WebP de public/images que falten. Las del repo ya vienen
# hechas, así que normalmente esto no hace nada (sólo un scan); sirve de red por
# si se commitea un hero sin correr el comando, o si el admin sube uno nuevo.
echo "▶ Optimizando imágenes estáticas..."
php artisan images:optimizar || echo "  ⚠ falló la optimización — se sirven los originales"

echo "▶ Caching config, routes and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

echo "▶ Fixing permissions..."
chown -R www-data:www-data storage bootstrap/cache

echo "▶ Starting services..."
exec "$@"
