FROM php:8.2-fpm
RUN apt-get update && apt-get install -y \
      apt-utils \
      libpq-dev \
      libpng-dev \
      libzip-dev \
      zip unzip \
      git && \
      docker-php-ext-install pdo_mysql && \
      docker-php-ext-install bcmath && \
      docker-php-ext-install gd && \
      docker-php-ext-install zip && \
      apt-get clean && \
      rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
COPY ./_docker/app/php.ini /usr/local/etc/php/conf.d/php.ini
RUN chmod 755 /usr/local/etc
WORKDIR /var/www
