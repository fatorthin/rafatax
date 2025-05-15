#!/bin/bash

# Script deployment untuk production
# Penggunaan: ./deploy.sh

echo "Starting deployment process..."

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "Detected PHP version: $PHP_VERSION"

# Ensure we're using PHP 8.4
if [[ $PHP_VERSION != 8.4* ]]; then
    echo "Warning: Expected PHP 8.4.x, but found $PHP_VERSION"
    echo "Make sure your production environment uses PHP 8.4"
    
    # Uncomment if your server has multiple PHP versions and can switch
    # echo "Switching to PHP 8.4..."
    # Using alternatives (for systems that use it)
    # sudo update-alternatives --set php /usr/bin/php8.4
    # Or specify full path to PHP 8.4 binary for subsequent commands
    # PHP_BIN=/usr/bin/php8.4
else
    PHP_BIN=php
fi

# Pull latest changes
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
$PHP_BIN artisan clear-compiled
$PHP_BIN artisan cache:clear
$PHP_BIN artisan config:clear
$PHP_BIN artisan view:clear
$PHP_BIN artisan route:clear

# Optimize Filament
$PHP_BIN artisan filament:optimize

# Run migrations
$PHP_BIN artisan migrate --force

# Optimize Laravel
$PHP_BIN artisan optimize

echo "Deployment completed successfully!" 