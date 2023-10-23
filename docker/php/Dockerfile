ARG TIMEZONE=Europe/Berlin
ARG PHP_VERSION=8.2

FROM php:${PHP_VERSION}-fpm

RUN apt update \
    && apt install -y zlib1g-dev g++ git libicu-dev zip libzip-dev libpq-dev zip jq \
    && docker-php-ext-install intl opcache pdo pdo_mysql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-configure zip \
      && docker-php-ext-install zip \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
      && docker-php-ext-install pdo pdo_pgsql pgsql

# redis
RUN pecl install --force redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis

# composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/app

EXPOSE 9000

RUN chown -R www-data:www-data /run /var/www/app

USER www-data
