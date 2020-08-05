FROM jakzal/phpqa:php7.4-alpine

RUN composer global require hirak/prestissimo --no-plugins --no-scripts
WORKDIR /app
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --prefer-dist --no-interaction --no-scripts
RUN composer install --prefer-dist --no-interaction --no-scripts
RUN composer dumpautoload -o
