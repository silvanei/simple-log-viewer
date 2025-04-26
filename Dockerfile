FROM php:8.3.8-cli-alpine3.20 AS development

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    TZ="America/Sao_Paulo"

COPY --from=composer:2.7.7 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
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