#!/bin/sh
set -eu

HTTP_PORT="${PORT:-80}"
sed -ri "s/^Listen [0-9]+$/Listen ${HTTP_PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${HTTP_PORT}>/" /etc/apache2/sites-available/*.conf

mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
php artisan storage:link --force >/dev/null 2>&1 || true

php artisan config:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${RUN_DEMO_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --class=DemoDeploymentSeeder --force
fi

if [ "${RUN_MEDIA_COPY:-false}" = "true" ]; then
    php artisan media:copy --from="${MEDIA_COPY_FROM:-public}" --to="${MEDIA_DISK:-public}"
fi

if [ "${RUN_SCHEDULER:-false}" = "true" ]; then
    su -s /bin/sh www-data -c 'php artisan schedule:work' &
fi

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

exec apache2-foreground
