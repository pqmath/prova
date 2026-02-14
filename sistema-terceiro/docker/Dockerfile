FROM webdevops/php-nginx-dev:8.2

ARG APP_ENV="local"
ARG APP_DEBUG="true"
ARG WEB_DOCUMENT_ROOT="/app/public"
ARG PHP_DATE_TIMEZONE="America/Sao_Paulo"

ENV APP_ENV=${APP_ENV} \
    APP_DEBUG=${APP_DEBUG} \
    WEB_DOCUMENT_ROOT=${WEB_DOCUMENT_ROOT} \
    PHP_DATE_TIMEZONE=${PHP_DATE_TIMEZONE} \
   XDEBUG_MODE="coverage,debug"

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

COPY . .

RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p database \
    && touch database/database.sqlite \
    && chown -R application:application /app \
    && chmod -R 777 storage bootstrap/cache \
    && chmod -R 775 database \
    && chmod 664 database/database.sqlite

USER application

RUN composer dump-autoload --optimize

USER root
