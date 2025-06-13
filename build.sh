#!/usr/bin/env bash

# Install dependencies
composer install --no-dev --optimize-autoloader

# Create database directory if it doesn't exist
mkdir -p database

# Create SQLite database
touch database/database.sqlite

# Set permissions
chmod 664 database/database.sqlite

# Generate application key
php artisan key:generate --force

# Clear and cache configuration
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

echo "Build completed successfully!" 