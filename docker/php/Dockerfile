ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-apache

RUN apt-get update && apt-get install -y \
        socat \
        libpng-dev \
        libjpeg-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        mbstring \
        intl \
        zip \
        opcache \
        bcmath \
    && printf 'DirectoryIndex default.php index.php index.html\n' \
       > /etc/apache2/conf-available/xpanel-directory-index.conf \
    && a2enconf xpanel-directory-index

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
