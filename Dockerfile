FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        firebird-dev \
        libpq-dev \
        libsqlite3-dev \
        unzip \
    && docker-php-ext-install pdo_firebird pdo_mysql pdo_pgsql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
