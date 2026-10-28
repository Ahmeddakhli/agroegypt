FROM composer:2 AS vendor
WORKDIR /app
COPY . .
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader --ignore-platform-reqs

FROM node:22-alpine AS assets
WORKDIR /app
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm ci && npm run build

FROM php:8.4-cli
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl gd zip exif pdo_sqlite
WORKDIR /app
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
RUN php artisan package:discover && php artisan filament:upgrade

ENV APP_ENV=production APP_DEBUG=false DB_CONNECTION=sqlite DB_DATABASE=/app/database/database.sqlite PHP_CLI_SERVER_WORKERS=4 LOG_CHANNEL=stderr

# ponytail: Render free disk is ephemeral, so the DB is rebuilt from the seeders on every boot; move to a managed DB + object storage once admin edits must persist.
CMD touch database/database.sqlite \
    && php artisan migrate:fresh --seed --force \
    && php artisan storage:link \
    && { [ -z "$ADMIN_EMAIL" ] || php artisan app:create-admin --name=Admin --email="$ADMIN_EMAIL" --password="$ADMIN_PASSWORD"; } \
    && php artisan optimize \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
