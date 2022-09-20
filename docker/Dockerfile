FROM composer as composer

ARG ENABLE_DEV_DEPS=false
COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer check-platform-reqs
RUN if [ "$ENABLE_DEV_DEPS" = "true" ] ; then composer install --prefer-dist --no-interaction --no-scripts; else \
    composer install --prefer-dist --no-interaction --no-scripts --no-dev; fi
RUN composer dumpautoload -o

FROM php:8.1.10-cli-alpine
RUN apk --no-cache add \
  # needed by intl
  icu-dev
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install calendar
RUN docker-php-ext-install intl
RUN docker-php-ext-install opcache

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/memory_limit.ini /usr/local/etc/php/conf.d/memory-limit.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/
RUN mkdir -p test-results/unit
COPY src src
COPY public public
COPY tests tests

COPY --from=composer /app/vendor /var/www/vendor
RUN test -d /var/www/vendor
CMD ["php", "-f", "/var/www/public/consumer.php"]
