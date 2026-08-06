# Worker image for Horizon and Reverb: PHP 8.4 CLI with the extensions the
# queue and websocket processes need. The app code is bind-mounted at runtime.
FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
