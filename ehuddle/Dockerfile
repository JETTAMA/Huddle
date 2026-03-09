FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite

# Configure Apache to allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy application source
# (.dockerignore will exclude sensitive folders)
COPY . .

# Configure Apache DocumentRoot and permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Ensure data and uploads are writable by the web server
RUN mkdir -p /var/www/html/data /var/www/html/uploads/avatars /var/www/html/uploads/banners /var/www/html/uploads/posts && \
    chown -R www-data:www-data /var/www/html/data /var/www/html/uploads && \
    chmod -R 775 /var/www/html/data /var/www/html/uploads

# Set PHP configuration for larger uploads (optional but recommended)
RUN echo "upload_max_filesize = 10M\npost_max_size = 10M" > /usr/local/etc/php/conf.d/uploads.ini

# Expose port 80
EXPOSE 80

# The default Apache entrypoint will handle starting the server
