FROM php:7.4-cli-alpine

WORKDIR /var/www/
COPY docker/mock-notify/public public
COPY docker/mock-notify/src src

CMD php -S 0.0.0.0:9123 public/server.php
