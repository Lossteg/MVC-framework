FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    libintl \
    icu-dev \
    libxml2-dev \
    postgresql-dev \
    imagemagick-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    linux-headers

RUN docker-php-ext-install \
    intl \
    bcmath \
    opcache \
    pdo_pgsql \
    gd

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apk del .build-deps

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

# Очищаем кэш
RUN rm -rf /var/cache/apk/* && \
    rm -rf /tmp/*

# Запускаем PHP-FPM
CMD ["php-fpm"]