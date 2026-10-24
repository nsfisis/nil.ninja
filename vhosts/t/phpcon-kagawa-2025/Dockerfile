FROM php:8.5.12-cli AS base

RUN docker-php-ext-configure sockets && \
    docker-php-ext-install -j$(nproc) sockets

FROM base AS vendor

RUN apt-get update && \
    apt-get install -y --no-install-recommends git unzip && \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-interaction

FROM base

# Defense in depth: even if code execution is somehow achieved, make it hard to spawn a shell or reach out.
RUN { \
        echo 'disable_functions = exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec,dl,putenv,mail'; \
        echo 'allow_url_fopen = Off'; \
        echo 'allow_url_include = Off'; \
        echo 'open_basedir = /app'; \
        echo 'memory_limit = 64M'; \
    } > "$PHP_INI_DIR/conf.d/zz-hardening.ini"

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor
COPY index.php ./
COPY src ./src

# Owned by root and only readable by the app user, so that it cannot modify its own code.
RUN chmod -R a+rX,go-w /app

USER www-data

EXPOSE 8080

CMD ["php", "index.php"]
