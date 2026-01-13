FROM php:8.5.1-cli-alpine3.23 AS development

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    TZ="America/Sao_Paulo"

COPY --from=composer:2.9.2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

# Install xdebug for coverage and infection
RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS

# Install dependencies
COPY composer.json composer.lock /app/
RUN composer install --prefer-dist --no-scripts --no-dev --no-autoloader \
    && composer clear-cache \
    && rm -rf /var/cache/* \
    && rm -Rf /tmp/*

COPY . /app

# Finish composer
RUN composer dump-autoload --no-scripts --no-dev --optimize \
    && rm -Rf /tmp/* \
    && composer clear-cache

USER www-data
EXPOSE 8080

CMD ["php", "bin/server.php"]

FROM development AS production

ENTRYPOINT ["php", "bin/server.php"]
