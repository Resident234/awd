FROM php:8.4-cli-bookworm

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

RUN curl -fsSL https://github.com/aptible/supercronic/releases/download/v0.2.29/supercronic-linux-amd64 \
        -o /usr/local/bin/supercronic \
    && chmod +x /usr/local/bin/supercronic \
    && groupadd --gid "${GID}" appuser \
    && useradd --uid "${UID}" --gid "${GID}" --create-home --shell /bin/bash appuser \
    && mkdir -p /var/www/html \
    && chown -R appuser:appuser /var/www/html

COPY php.ini /usr/local/etc/php/conf.d/99-yii-cli.ini

WORKDIR /var/www/html

USER appuser

COPY --chown=appuser:appuser entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
