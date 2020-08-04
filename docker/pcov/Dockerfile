FROM composer as composer
# Allow parallel downloads
RUN composer global require hirak/prestissimo --no-plugins --no-scripts
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --prefer-dist --no-interaction --no-scripts
RUN composer dumpautoload -o

FROM php:7.4-cli

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN pecl install pcov
RUN docker-php-ext-enable pcov
RUN docker-php-ext-install calendar
RUN docker-php-ext-configure calendar

WORKDIR /var/www/

RUN mkdir -p test-results/{unit,pcov}

COPY src src
COPY tests tests
COPY phpunit.xml phpunit.xml

COPY --from=composer /app/vendor /var/www/vendor
RUN test -d /var/www/vendor
RUN ls -la /var/www/vendor/bin
