FROM php:8.3.28-apache

WORKDIR /var/www/html

# Copy files
COPY . /var/www/html/

# Fix Apache ServerName warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Allow .htaccess and directory access
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Enable rewrite module
RUN a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]
