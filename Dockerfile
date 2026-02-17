# Development stage
FROM dunglas/frankenphp:1.11.2-php8.5-alpine AS development

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    TZ="America/Sao_Paulo"

WORKDIR /app

# Install composer
COPY --from=composer:2.9.2 /usr/bin/composer /usr/local/bin/composer

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

EXPOSE 8080 443 443/udp

CMD ["frankenphp", "server", "--config", "Caddyfile"]

# Production stage
FROM dunglas/frankenphp:1.11.2-php8.5-alpine AS production

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    TZ="America/Sao_Paulo"

WORKDIR /app

# Install composer
COPY --from=composer:2.9.2 /usr/bin/composer /usr/local/bin/composer

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

EXPOSE 8080 443 443/udp

CMD ["frankenphp", "server", "--config", "Caddyfile"]
