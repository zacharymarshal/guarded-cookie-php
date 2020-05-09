FROM php:7.4-fpm-alpine

# git is for composer
# zip is for composer

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
    && apk add --no-cache \
        git \
        libzip-dev \
    && docker-php-ext-install \
        opcache\
        zip \
    && pecl install xdebug-2.9.4 \
    && docker-php-ext-enable xdebug \
    && apk del --no-cache .build-deps

RUN curl -sS https://getcomposer.org/installer >composer-setup.php \
    && php composer-setup.php --quiet \
        --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

WORKDIR /app
