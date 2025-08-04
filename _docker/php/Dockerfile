FROM php:8.3-fpm-alpine

# Установка системных зависимостей
RUN apk add --no-cache \
 curl \
 libpng-dev \
 libjpeg-turbo-dev \
 libwebp-dev \
 libxpm-dev \
 oniguruma-dev \
 libxml2-dev \
 zip \
 unzip \
 nodejs \
 npm \
 shadow \
 postgresql-dev \
 acl \
 bash \
 autoconf \
 g++ \
 make

# Установка PHP расширений (ВКЛЮЧАЕМ REDIS)
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd xml \
 && pecl install redis \
 && docker-php-ext-enable redis

# Установка лимитов для больших загрузок (post_max_size, upload_max_filesize)
RUN echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini && \
 echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini && \
 echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Добавляем fixuid для коррекции прав UID/GID
RUN apk add --no-cache curl tar && \
 mkdir -p /usr/local/bin && \
 curl -fSL https://github.com/boxboat/fixuid/releases/download/v0.6.0/fixuid-0.6.0-linux-amd64.tar.gz | tar -xzf - -C /usr/local/bin && \
 chown root:root /usr/local/bin/fixuid && \
 chmod 4755 /usr/local/bin/fixuid && \
 mkdir -p /etc/fixuid && \
 printf "user: www-data\ngroup: www-data\n" > /etc/fixuid/config.yml

WORKDIR /var/www

# Копирование файлов проекта
COPY . /var/www

# Создаем скрипт для установки прав и Artisan команд
RUN echo $'#!/bin/sh \n\
chmod -R 775 /var/www/storage \n\
php artisan storage:link --force || true \n\
rm -rf /var/www/storage/app/public/* \n\
php artisan migrate --force \n\
php artisan storage:link \n\
exec "$@"' > /entrypoint.sh && chmod +x /entrypoint.sh

# Установка зависимостей Composer
RUN composer install --optimize-autoloader --no-interaction

# Установка NPM зависимостей
RUN npm ci

ENTRYPOINT ["/usr/local/bin/fixuid", "-q", "/entrypoint.sh"]
CMD ["php-fpm"]
