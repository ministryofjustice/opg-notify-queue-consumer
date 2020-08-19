FROM php:7.4-cli-alpine

WORKDIR /var/www/
COPY docker/test-mock-service/public/server.php server.php

CMD php -S 0.0.0.0:9123 server.php
