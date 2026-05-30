FROM php:8.3-apache

# System packages + PHP extensions Laravel/TiDB need.
# ca-certificates provides the CA bundle used for TiDB Cloud's TLS connection.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip ca-certificates \
        libzip-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip bcmath \
    && update-ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Composer (from the official Composer image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Enable Apache modules used by the API
RUN a2enmod rewrite headers

# Point Apache's document root at Laravel's public/ directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Render routes external traffic to port 10000
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

WORKDIR /var/www/html

# Copy the application source (vendor/ is excluded via .dockerignore so it is
# always installed fresh below)
COPY . /var/www/html/

# Install PHP dependencies — this generates vendor/autoload.php.
# --no-scripts avoids running artisan during build (no env/APP_KEY yet);
# package discovery happens at runtime into the writable bootstrap/cache.
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts

# Laravel needs these writable at runtime
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 775 storage bootstrap/cache

EXPOSE 10000
