FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    wget \
    unzip \
    zip \
    ca-certificates \
    linux-libc-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pcntl \
    sockets \
    && docker-php-ext-enable pcntl sockets

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/composer
