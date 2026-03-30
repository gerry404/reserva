#!/usr/bin/env bash
set -e

# Install dependencies
composer install --no-dev --optimize-autoloader

# Laravel production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link || true
