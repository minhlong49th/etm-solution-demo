#!/usr/bin/env bash

echo "Running composer install..."
composer install --no-dev --working-dir=/var/www/html

echo "Running dump autoload..."
composer dump-autoload

echo "Running migrations..."
php artisan migrate --force
