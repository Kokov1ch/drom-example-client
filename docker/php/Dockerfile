FROM php:8.3.13-fpm-alpine3.19

ENV XDEBUG_VERSION=3.3.2
ENV DOCKER_APP_UID=1000

RUN apk add --no-cache bash shadow make linux-headers

RUN apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS \
    && apk add --no-cache \
       libzip-dev \
    && docker-php-ext-install \
        opcache \
        zip \
    && pecl install xdebug-$XDEBUG_VERSION \
    && docker-php-ext-enable xdebug \
    && apk del .phpize-deps

COPY --from=composer:2.8.2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www
RUN usermod -u $DOCKER_APP_UID www-data
USER www-data
