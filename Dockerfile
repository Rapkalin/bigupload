# =============================================================================
# Multi-stage Dockerfile — Symfony backend
# Stages: base → development | production
# Used by docker-compose (development) and GitLab CI (production)
# =============================================================================

# -----------------------------------------------------------------------------
# Base: PHP 8.4-FPM with required extensions
# -----------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-libs \
    libzip \
    unzip \
    zip

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    linux-headers \
  && docker-php-ext-install intl opcache pdo_mysql zip \
  && pecl install apcu \
  && docker-php-ext-enable apcu \
  && apk del .build-deps

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/api

COPY docker/php.ini     /usr/local/etc/php/conf.d/app.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# -----------------------------------------------------------------------------
# Development: mounts source via volume, no COPY needed
# -----------------------------------------------------------------------------
FROM base AS development

ENV APP_ENV=dev

# -----------------------------------------------------------------------------
# Production: copies source, installs prod deps, warms cache
# -----------------------------------------------------------------------------
FROM base AS production

ENV APP_ENV=prod

COPY . .

RUN cp .env.example .env \
  && composer install \
      --no-dev \
      --optimize-autoloader \
      --no-interaction \
      --no-scripts \
  && php bin/console cache:warmup --env=prod \
  && chown -R www-data:www-data var/

USER www-data
