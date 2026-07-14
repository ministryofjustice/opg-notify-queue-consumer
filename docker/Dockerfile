FROM composer:latest@sha256:7725eb4545c438629ae8bde3ef0bb9a5038ef566126ad878442a69007242d267 AS composer-prod

COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer check-platform-reqs
RUN composer install --prefer-dist --no-interaction --no-scripts --no-dev
RUN composer dumpautoload -o

FROM composer:latest@sha256:7725eb4545c438629ae8bde3ef0bb9a5038ef566126ad878442a69007242d267 AS composer-test

COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer check-platform-reqs
RUN composer install --prefer-dist --no-interaction --no-scripts
RUN composer dumpautoload -o

FROM php:8.5.8-cli-alpine@sha256:8d7090ce03736b6ecfd739d87a46ab59b5d1d0d837f1ac7c5d2702f1d312f5a0 AS build
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
