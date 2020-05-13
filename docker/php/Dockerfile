FROM php:7.4-fpm-alpine

# git is for composer
# zip is for composer
# inotify-tools is for watching tests

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
    && apk add --no-cache \
        git \
        libzip-dev \
        inotify-tools \
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

RUN curl -sS https://cs.symfony.com/download/php-cs-fixer-v2.phar > /usr/local/bin/php-cs-fixer \
    && chmod 0755 /usr/local/bin/php-cs-fixer

RUN curl -LsS https://phar.phpunit.de/phpunit-9.phar > /usr/local/bin/phpunit \
    && chmod 0755 /usr/local/bin/phpunit

WORKDIR /app
