#!/bin/sh
# Container entrypoint (ADR-033). Runs as root; every artisan call is dropped to
# www-data so nothing in storage/ or bootstrap/cache/ ends up root-owned.
#
#   default command (supervisord): caches, readiness check, migrate, then start
#                                  nginx + php-fpm + worker + scheduler
#   any other command:             caches, then run it as www-data, e.g.
#                                  docker compose run --rm app php artisan about
set -e

cd /var/www/html

as_app() {
    setpriv --reuid=www-data --regid=www-data --init-groups "$@"
}

# A fresh named volume is seeded from the image, but an older volume may predate
# a directory the current release expects.
mkdir -p storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs storage/app/private
chown -R www-data:www-data storage bootstrap/cache

# Built here rather than at image build time: they capture the runtime .env.
as_app php artisan config:cache --no-interaction
as_app php artisan route:cache --no-interaction
as_app php artisan event:cache --no-interaction
as_app php artisan view:cache --no-interaction

if [ "$1" = "supervisord" ]; then
    # docs/13-deployment.md "Pre-release check": stop before migrating if the
    # production checklist fails. The container exits and the reason is in
    # `docker compose logs app`.
    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        as_app php artisan app:check-production-readiness --no-interaction
        as_app php artisan migrate --force --no-interaction
    fi

    exec "$@"
fi

exec setpriv --reuid=www-data --regid=www-data --init-groups "$@"
