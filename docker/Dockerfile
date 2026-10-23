FROM composer:2.10.3@sha256:da624c2c705d54e2f5a9bb77a5dc6c7d091711433ebefd7035672e84c2010fee  AS composer-prod

COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer check-platform-reqs
RUN composer install --prefer-dist --no-interaction --no-scripts --no-dev
RUN composer dumpautoload -o

FROM composer:2.10.3@sha256:da624c2c705d54e2f5a9bb77a5dc6c7d091711433ebefd7035672e84c2010fee  AS composer-test

COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer check-platform-reqs
RUN composer install --prefer-dist --no-interaction --no-scripts
RUN composer dumpautoload -o

FROM php:8.5.12-cli-alpine@sha256:d0cc453a4a24eb5e2e70c4657cf8bddedce4c4c33a9a348409cdc7d4b9762e94 AS build
RUN apk --no-cache add \
  # needed by intl
  icu-dev
# Alpine Image Vulnerabilities
RUN apk upgrade --no-cache busybox c-ares curl libcurl musl-utils nghttp2-libs libcrypto3 libsodium libssl3 libxml2 sqlite-libs xz xz-libs zlib

RUN docker-php-ext-install pcntl
RUN docker-php-ext-install calendar
RUN docker-php-ext-install intl

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/memory_limit.ini /usr/local/etc/php/conf.d/memory-limit.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/session_upload.ini /usr/local/etc/php/conf.d/session-upload.ini

WORKDIR /var/www/
RUN mkdir -p test-results/unit
COPY --chown=www-data:www-data src src
COPY --chown=www-data:www-data public public

FROM build AS test

RUN apk add --no-cache --virtual .php-pie-deps autoconf build-base libtool
COPY --from=ghcr.io/php/pie:bin@sha256:bc894d47ddfc4b805bf88f4528fa7c34ecca383f4bc30480ca67ec0a4d3f2cfe /pie /usr/bin/pie
RUN pie install pecl/pcov:1.0.12
RUN apk del .php-pie-deps

COPY --from=composer-test /app/vendor /var/www/vendor
RUN test -d /var/www/vendor
COPY --chown=www-data:www-data tests tests
USER "www-data"


FROM build AS production
COPY --from=composer-prod /app/vendor /var/www/vendor
RUN test -d /var/www/vendor
USER "www-data"
CMD ["php", "-f", "/var/www/public/consumer.php"]
