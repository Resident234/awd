FROM php:8.4-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-install -j2 \
        intl \
        mbstring \
        opcache \
        pdo_pgsql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupadd --gid "${GID}" appuser \
    && useradd --uid "${UID}" --gid "${GID}" --create-home --shell /bin/bash appuser \
    && mkdir -p /var/www/html /tmp/composer-cache \
    && chown -R appuser:appuser /var/www/html /tmp/composer-cache

COPY php.ini /usr/local/etc/php/conf.d/99-yii-dev.ini
COPY www.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

# PHP-FPM starts its master process as root and runs workers as appuser.
# Composer and Yii console commands are run explicitly as appuser by Compose.
CMD ["php-fpm", "-F"]
