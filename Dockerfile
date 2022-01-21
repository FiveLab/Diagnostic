FROM php:8.1-cli

MAINTAINER Vitaliy Zhuk <zhuk2205@gmail.com>

ARG XDEBUG_REMOTE_HOST='host.docker.internal'
ARG XDEBUG_REMOTE_PORT=9000

ENV PHP_IDE_CONFIG='serverName=diagnostic'

RUN \
    apt-get update && \
    apt-get install -y --no-install-recommends \
        git ssh-client \
        zip unzip

# Install additional php extensions
RUN \
    apt-get install -y --no-install-recommends \
        librabbitmq-dev && \
    docker-php-ext-install pdo pdo_mysql sockets && \
    printf '\n' | pecl install amqp-1.11.0 && \
    printf "\n" | pecl install redis && \
    yes | pecl install xdebug && \
    docker-php-ext-enable amqp xdebug redis sockets

# Configure XDebug
RUN \
    echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_connect_back=off" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=${XDEBUG_REMOTE_HOST}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_port=${XDEBUG_REMOTE_PORT}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.max_nesting_level=1500" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

WORKDIR /code
