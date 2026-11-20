# SuiteCRM 8.10 runtime: PHP 8.3 + Apache (matches the composer platform and the Azure target).
FROM php:8.3.37-apache-bookworm

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl ca-certificates \
        libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
        libicu-dev libzip-dev libonig-dev libcurl4-openssl-dev libxml2-dev \
        libldap2-dev libc-client-dev libkrb5-dev libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mysqli gd intl zip curl mbstring ldap imap soap bcmath exif opcache pcntl \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

# Composer (used inside the container for "composer install").
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Apache: DocumentRoot = public/, AllowOverride All (SuiteCRM relies on .htaccess mod_rewrite rules).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && printf '<Directory ${APACHE_DOCUMENT_ROOT}>\n    Options FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/suitecrm.conf \
    && a2enconf suitecrm \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf && a2enconf servername

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-suitecrm.ini

ENV COMPOSER_MEMORY_LIMIT=-1 COMPOSER_ALLOW_SUPERUSER=1
# Named-volume mount points owned by www-data, so a fresh volume is writable by Apache.
# (Bind-mounted files are writable by any user on Docker Desktop.)
RUN mkdir -p /var/www/html/var /var/www/html/cache /var/www/html/logs \
        /var/www/html/public/legacy/cache /var/www/html/public/legacy/upload \
    && chown www-data:www-data /var/www/html/var /var/www/html/cache /var/www/html/logs \
        /var/www/html/public/legacy/cache /var/www/html/public/legacy/upload

WORKDIR /var/www/html
