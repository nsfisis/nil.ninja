FROM php:8.4-cli

RUN apt-get update && \
    apt-get install -y git unzip

RUN docker-php-ext-configure sockets && \
    docker-php-ext-install -j$(nproc) sockets

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

EXPOSE 8080

CMD ["php", "index.php"]
