#!/bin/sh
set -e

cd /var/www/html

echo "▶ Linking storage..."
php artisan storage:link --force 2>/dev/null || true

echo "▶ Caching config, routes and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "▶ Fixing permissions..."
chown -R www-data:www-data storage bootstrap/cache

echo "▶ Starting services..."
exec "$@"
