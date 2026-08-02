# ─────────────────────────────────────────────────────────────────────────────
# Nuvo API
#
# The same image runs all three roles — web, queue worker, scheduler — because
# they execute the same code and must never drift apart. The role is chosen by
# the command in docker-compose.yml.
#
# FrankenPHP rather than `php artisan serve`: the artisan server is a
# single-process development tool where one slow request blocks every other
# client, which is exactly the wrong shape for a booking page that gets shared
# to thirty people at once on WhatsApp.
# ─────────────────────────────────────────────────────────────────────────────

FROM dunglas/frankenphp:1-php8.3 AS base

RUN install-php-extensions \
        pdo_pgsql \
        pgsql \
        gd \
        intl \
        zip \
        opcache \
        pcntl \
    && apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# ─── Dependencies ────────────────────────────────────────────────────────
# Installed before the source is copied, so editing a controller does not
# invalidate the vendor layer.
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

# ─── Application ─────────────────────────────────────────────────────────
FROM base AS app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && mkdir -p storage/framework/cache/data \
                storage/framework/sessions \
                storage/framework/views \
                storage/app/public \
                storage/logs \
                bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# The shipped production ini: OPcache on, display_errors off.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/opcache.ini "$PHP_INI_DIR/conf.d/zz-opcache.ini"

ENV APP_ENV=production \
    APP_DEBUG=false \
    SERVER_NAME=:8080

EXPOSE 8080

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]

CMD ["frankenphp", "php-server", "--root", "public/", "--listen", ":8080"]
