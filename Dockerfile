# ShaloTrack Fleet Management Portal — production image
#
# Three stages, only the last one ships:
#   1. vendor  — Composer production dependencies (no dev packages)
#   2. assets  — Vite/Tailwind build → public/build
#   3. runtime — PHP-FPM + nginx, non-root, no Node, no Composer, no git
#
# Secrets are NEVER baked in. APP_KEY and every other environment-specific
# value is injected at `docker run` time (sourced from AWS SSM on the EC2).
#
# Container listens on 8080 (non-root cannot bind to 80).
# Host mapping on the Admin EC2: -p 8080:8080

ARG PHP_VERSION=8.3
ARG NODE_VERSION=22

# -----------------------------------------------------------------------------
# Base: PHP-FPM + the extensions this app needs at runtime
#   gd      → DomPDF renders the base64 PNG charts/logo in Stats & Trip PDFs
#   opcache → compiled PHP kept in memory (big CPU win on a t3.micro)
# -----------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS php-base

RUN apk add --no-cache libpng libjpeg-turbo freetype \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libpng-dev libjpeg-turbo-dev freetype-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install -j"$(nproc)" gd opcache \
 && apk del .build-deps \
 && rm -rf /tmp/* /var/cache/apk/*

# -----------------------------------------------------------------------------
# Stage 1: Composer dependencies
# -----------------------------------------------------------------------------
FROM php-base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

# Dependency layer first — only rebuilt when composer.json/lock change.
COPY composer.json composer.lock ./
RUN composer install \
      --no-dev --no-scripts --no-autoloader \
      --no-interaction --no-progress --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts \
 && php artisan package:discover --ansi

# -----------------------------------------------------------------------------
# Stage 2: Frontend assets (Vite + Tailwind)
# -----------------------------------------------------------------------------
FROM node:${NODE_VERSION}-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
RUN npm run build

# -----------------------------------------------------------------------------
# Stage 3: Runtime
# -----------------------------------------------------------------------------
FROM php-base AS runtime

RUN apk add --no-cache nginx \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 # Drop the stock FPM pool files (listen on 0.0.0.0, run-as-root defaults).
 && rm -f /usr/local/etc/php-fpm.d/*.conf /usr/local/etc/php-fpm.d/*.default \
 && rm -f /etc/nginx/http.d/default.conf

COPY docker/php.ini       /usr/local/etc/php/conf.d/zz-fleet.ini
COPY docker/php-fpm.conf  /usr/local/etc/php-fpm.d/zz-fleet.conf
COPY docker/nginx.conf    /etc/nginx/nginx.conf
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/entrypoint
# Guard against CRLF line endings if the script was saved on Windows.
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint

WORKDIR /var/www/html

# Application code is owned by root → read-only for the www-data process.
# Only storage/ and bootstrap/cache/ are writable (below).
COPY --from=vendor /app ./
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p \
      storage/app/private \
      storage/fonts \
      storage/framework/cache/data \
      storage/framework/sessions \
      storage/framework/views \
      storage/logs \
      bootstrap/cache \
      /tmp/nginx \
 && chown -R www-data:www-data storage bootstrap/cache /tmp/nginx

# Production-safe, NON-SECRET defaults. Every one of these can be overridden
# with `docker run -e`. Secrets (APP_KEY, API keys) are never set here.
ENV APP_NAME="ShaloTrack Fleet" \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=warning \
    SESSION_DRIVER=file \
    SESSION_LIFETIME=120 \
    SESSION_COOKIE=shalotrack_fleet_session \
    SESSION_DOMAIN=null \
    SESSION_SECURE_COOKIE=true \
    SESSION_HTTP_ONLY=true \
    SESSION_SAME_SITE=lax \
    SESSION_ENCRYPT=true \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync \
    BROADCAST_CONNECTION=log \
    FILESYSTEM_DISK=local \
    MAIL_MAILER=log \
    SHALOTRACK_API_BASE_URL=https://api.shalotrack.com

USER www-data

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD wget -q -O /dev/null http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint"]