FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libpq-dev \
    curl \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        xml \
        zip \
        bcmath \
        pcntl \
        intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache-docker.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
