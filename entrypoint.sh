#!/bin/bash
set -e

PORT=${PORT:-8000}

php artisan vendor:publish --tag=laravel-mail --force
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

php artisan migrate --force --no-interaction

php artisan storage:link --force 2>/dev/null || true

php artisan serve --host=0.0.0.0 --port=$PORT