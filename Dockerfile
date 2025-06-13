# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www/html

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www/html

# Create necessary directories BEFORE installing dependencies
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/bootstrap/cache

# Set proper permissions BEFORE composer install
RUN chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Create SQLite database file
RUN touch /var/www/html/database/database.sqlite \
    && chown www-data:www-data /var/www/html/database/database.sqlite \
    && chmod 664 /var/www/html/database/database.sqlite

# Configure Apache
RUN a2enmod rewrite
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Create basic .env file for build process
RUN echo "APP_NAME=Laravel" > .env \
    && echo "APP_ENV=production" >> .env \
    && echo "APP_KEY=" >> .env \
    && echo "APP_DEBUG=false" >> .env \
    && echo "APP_TIMEZONE=UTC" >> .env \
    && echo "APP_URL=http://localhost" >> .env \
    && echo "DB_CONNECTION=sqlite" >> .env \
    && echo "DB_DATABASE=/var/www/html/database/database.sqlite" >> .env \
    && echo "LOG_CHANNEL=stderr" >> .env \
    && echo "CACHE_DRIVER=file" >> .env \
    && echo "SESSION_DRIVER=file" >> .env \
    && echo "QUEUE_CONNECTION=sync" >> .env

# Generate application key and optimize Laravel
RUN php artisan key:generate --force \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan migrate --force

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"] 