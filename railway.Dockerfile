FROM dunglas/frankenphp:php8.4.15-bookworm

RUN apt-get update && \
    apt-get install -y wget gnupg && \
    pecl install mongodb && \
    echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini