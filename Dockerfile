# syntax=docker/dockerfile:1

# One container runs the whole application under supervisord: nginx, php-fpm,
# the queue worker and the scheduler (ADR-033). Migrations run in the
# entrypoint before supervisord starts. See docs/13-deployment.md.

ARG PHP_VERSION=8.3
ARG NODE_VERSION=22

# ---------------------------------------------------------------------------
# base: PHP runtime + extensions shared by build and runtime stages
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-bookworm AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

# pdo_pgsql: ADR-004. intl/bcmath: framework helpers. zip + the xml family:
# openspout (Excel import/export). pcntl: queue:work signal handling.
RUN install-php-extensions pdo_pgsql pgsql intl zip bcmath opcache pcntl

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# node: source of the Node runtime copied into the build stage
# ---------------------------------------------------------------------------
FROM node:${NODE_VERSION}-bookworm-slim AS node

# ---------------------------------------------------------------------------
# build: Composer + Vite. Needs PHP and Node together because the Wayfinder
# Vite plugin runs `php artisan wayfinder:generate` during `vite build`.
# ---------------------------------------------------------------------------
FROM base AS build

ARG VITE_APP_NAME="ERP Gateway"
ENV VITE_APP_NAME=${VITE_APP_NAME}

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Dependency layers first so a code-only change does not reinstall them.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund

COPY . .

RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && npm run build \
    && rm -rf node_modules

# ---------------------------------------------------------------------------
# app: nginx + php-fpm + worker + scheduler under supervisord
# ---------------------------------------------------------------------------
FROM base AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/conf.d/ "$PHP_INI_DIR/conf.d/"
COPY docker/php/fpm/zz-pool.conf /usr/local/etc/php-fpm.d/zz-pool.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions \
        storage/framework/views storage/logs storage/app/private bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# supervisord and the nginx / php-fpm masters run as root (port 80, dropping
# to www-data for their workers); every PHP process that touches the app --
# fpm workers, queue worker, scheduler, artisan in the entrypoint -- runs as
# www-data.
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS -o /dev/null http://127.0.0.1/up || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
