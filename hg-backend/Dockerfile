FROM php:8.3-apache

# Install PDO MySQL extension for TiDB connection
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite (needed for most REST APIs)
RUN a2enmod rewrite headers

# Set the document root to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Change Apache to listen on port 10000 (Render's default)
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Copy your project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000