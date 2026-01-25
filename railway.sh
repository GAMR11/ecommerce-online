#!/bin/bash
composer install --no-dev --optimize-autoloader --ignore-platform-reqs
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan serve --host=0.0.0.0 --port=$PORT
