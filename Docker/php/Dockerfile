FROM php:8.3-cli-alpine

# -------------------------------------------------------
# Timezone: America/Sao_Paulo
# -------------------------------------------------------
ENV TZ=America/Sao_Paulo
RUN apk add --no-cache tzdata \
    && cp /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo "$TZ" > /etc/timezone

# -------------------------------------------------------
# Ferramentas extras: bash, vim, composer
# -------------------------------------------------------
RUN apk add --no-cache \
    bash \
    vim \
    git \
    curl \
    unzip

# -------------------------------------------------------
# Dependências de compilação para extensões PHP + Swoole
# -------------------------------------------------------
RUN apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    openssl-dev \
    curl-dev \
    $PHPIZE_DEPS

# -------------------------------------------------------
# Extensões PHP exigidas pelo Laravel 11
# -------------------------------------------------------
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        xml \
        opcache \
        sockets

# -------------------------------------------------------
# Swoole (Laravel Octane)
# -------------------------------------------------------
RUN pecl install swoole \
    && docker-php-ext-enable swoole

# -------------------------------------------------------
# Composer (última versão estável)
# -------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -------------------------------------------------------
# Configuração do PHP para produção
# -------------------------------------------------------
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Ajuste de timezone no php.ini
RUN sed -i "s|;date.timezone =|date.timezone = America/Sao_Paulo|" /usr/local/etc/php/php.ini

# -------------------------------------------------------
# Configuração do OPcache (otimizado para Swoole)
# -------------------------------------------------------
RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# -------------------------------------------------------
# Diretório de trabalho
# -------------------------------------------------------
WORKDIR /var/www/html

# -------------------------------------------------------
# Usuário não-root para segurança
# -------------------------------------------------------
RUN addgroup -g 1000 laravel \
    && adduser -u 1000 -G laravel -s /bin/bash -D laravel

RUN chown -R laravel:laravel /var/www/html

USER laravel

EXPOSE 8000
