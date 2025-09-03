FROM php:8.2-fpm-alpine AS base
ARG DOMAIN_NAME
ENV DOMAIN_NAME=$DOMAIN_NAME

# Installing system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    linux-headers \
    postgresql-client

# Installing and activating PHP extensions
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install -j$(nproc) \
        pgsql \
        pdo_pgsql \
        intl \
        zip \
        opcache

# Composer installation
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer global require phpstan/phpstan \
    && composer global require phpunit/phpunit --no-scripts
ENV PATH="/root/.composer/vendor/bin:${PATH}"

# Define working directory
WORKDIR /var/www/html

COPY . .

# Copy configuration files
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]

CMD ["php-fpm"]