#!/bin/bash

# Script deployment untuk production
# Penggunaan: ./deploy.sh

echo "Starting deployment process..."

# Pull latest changes
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
php artisan clear-compiled
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Optimize Filament
php artisan filament:optimize

# Run migrations
php artisan migrate --force

# Optimize Laravel
php artisan optimize

echo "Deployment completed successfully!" 