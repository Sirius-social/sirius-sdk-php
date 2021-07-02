FROM php:7.4-cli

RUN apt-get update && apt-get install -y \
        libgmp-dev \
        && docker-php-ext-install gmp bcmath \
        && pecl install xdebug-2.9.8 \
        && docker-php-ext-enable xdebug
