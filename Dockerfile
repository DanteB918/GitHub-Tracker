FROM php:8.3-fpm

ARG USER_ID=1000

# Create docker user to match default local user
RUN useradd -m -u $USER_ID -s /bin/bash docker

RUN apt-get update && apt-get install -y \
    curl \
    libjpeg62-turbo-dev \
    libpng-dev \
    zlib1g-dev \
    libzip-dev \
    unzip \
    gnupg \
    gnupg1 \
    gnupg2 \
    wget \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install zip \
    && docker-php-ext-install pdo_mysql

COPY . /var/www/app
WORKDIR /var/www/app

COPY --from=composer:lts /usr/bin/composer /usr/local/bin/composer

RUN composer install

CMD ["php-fpm"]
