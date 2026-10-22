FROM composer:2.2.30@sha256:3d411d7de2b6e20e8869ddd13adefd218ba944f04af2875802ee269906181590  AS composer-prod

COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer check-platform-reqs
RUN composer install --prefer-dist --no-interaction --no-scripts --no-dev
RUN composer dumpautoload -o

FROM composer:2.2.30@sha256:3d411d7de2b6e20e8869ddd13adefd218ba944f04af2875802ee269906181590  AS composer-test

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
COPY --from=composer-test /app/vendor /var/www/vendor
RUN test -d /var/www/vendor
COPY --chown=www-data:www-data tests tests
RUN mkdir -p /var/www/test-results/functional
RUN chown -R www-data:www-data /var/www/test-results/functional
USER "www-data"
CMD ["php", "-f", "/var/www/public/consumer.php"]


FROM build AS production
COPY --from=composer-prod /app/vendor /var/www/vendor
RUN test -d /var/www/vendor
USER "www-data"
CMD ["php", "-f", "/var/www/public/consumer.php"]
