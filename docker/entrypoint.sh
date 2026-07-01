#!/bin/sh
set -e

# Ensure nginx/php-fpm (www-data) can read and write storage (bind mounts often arrive as root/nobody).
if [ -d storage ]; then
    mkdir -p \
        storage/app/temp \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs

    find storage -type d -exec chmod 775 {} +
    find storage -type f -exec chmod 664 {} +
    chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi

if [ -f artisan ] && [ -z "${APP_KEY:-}" ] && [ -f .env ]; then
    APP_KEY_VALUE=$(grep -E '^APP_KEY=' .env | cut -d= -f2-)
    if [ -z "$APP_KEY_VALUE" ]; then
        php artisan key:generate --force --no-interaction 2>/dev/null || true
    fi
fi

exec "$@"
