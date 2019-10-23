FROM php:7.2-cli

MAINTAINER Vitaliy Zhuk <zhuk2205@gmail.com>

RUN \
    apt-get update && \
    apt-get install -y --no-install-recommends \
        git ssh-client \
        zip unzip

# Install additional php extensions
RUN \
    apt-get install -y --no-install-recommends \
        librabbitmq-dev && \
    docker-php-ext-install pdo pdo_mysql && \
    printf '\n' | pecl install amqp && \
    printf "\n" | pecl install redis && \
    yes | pecl install xdebug && \
    docker-php-ext-enable amqp xdebug redis

# Install composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

WORKDIR /code
