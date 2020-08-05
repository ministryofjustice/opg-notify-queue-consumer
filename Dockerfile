FROM composer as composer
# Allow parallel downloads
RUN composer global require hirak/prestissimo --no-plugins --no-scripts
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --prefer-dist --no-interaction --no-scripts
RUN composer dumpautoload -o

FROM php:7.4-cli-alpine
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

ARG ENABLE_COVERAGE
# if coverage is enabled then install pcov and its dependencies
RUN if [ "$ENABLE_COVERAGE" = "true" ] ; then apk add --no-cache $PHPIZE_DEPS; fi
RUN if [ "$ENABLE_COVERAGE" = "true" ] ; then pecl install pcov && docker-php-ext-enable pcov; fi

WORKDIR /var/www/
RUN mkdir -p test-results/unit
COPY src src
COPY public public
COPY tests tests
COPY phpunit.xml phpunit.xml

COPY --from=composer /app/vendor /var/www/vendor
RUN test -d /var/www/vendor
CMD ["php", "-f", "/var/www/public/consumer.php"]
