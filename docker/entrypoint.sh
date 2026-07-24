#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache
touch "${DB_DATABASE:-/var/www/html/storage/app/database.sqlite}"
chown -R www-data:www-data storage bootstrap/cache "${DB_DATABASE:-/var/www/html/storage/app/database.sqlite}"

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generate one and add it to your .env, then restart:"
    echo ""
    echo "  docker compose run --rm eveil php artisan key:generate --show"
    echo ""
    exit 1
fi

php artisan migrate --force

exec "$@"
