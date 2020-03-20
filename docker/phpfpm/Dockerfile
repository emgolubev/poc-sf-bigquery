FROM php:7.4-fpm

RUN apt-get update && \
    apt-get install -y libonig-dev libpng-dev libjpeg-dev libpq-dev zip unzip sudo wget zlib1g-dev libicu-dev libzip-dev libzstd-dev && \
    rm -rf /var/lib/apt/lists/*

RUN yes | pecl install igbinary

RUN docker-php-ext-install intl && \
    docker-php-ext-install gd && \
    docker-php-ext-install mbstring && \
    docker-php-ext-install opcache && \
    docker-php-ext-install zip && \
    docker-php-ext-install calendar && \
    docker-php-ext-install sockets && \
    docker-php-ext-enable igbinary

RUN rm -rf /var/www/html \
    && chmod 0777 /tmp/