#!/bin/bash

# Configure Git safe directory to avoid ownership warnings
git config --global --add safe.directory /var/www/html

# Set proper ownership and permissions for Laravel directories
if [ -d "/var/www/html/storage" ]; then
    chown -R www-data:www-data /var/www/html/storage
    chmod -R 775 /var/www/html/storage
fi

if [ -d "/var/www/html/bootstrap/cache" ]; then
    chown -R www-data:www-data /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/bootstrap/cache
fi

# Create storage subdirectories if they don't exist
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/app/public

# Set proper ownership and permissions for storage subdirectories
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Execute the main command
exec "$@" 