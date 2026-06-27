#!/usr/bin/env bash
set -euo pipefail

PORT="${PORT:-8080}"

if [ ! -f .env ]; then
  cp .env.example .env
fi

php artisan key:generate --force --no-interaction || true
php artisan storage:link || true

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate:refresh --force --no-interaction
php artisan db:seed --force --no-interaction || true

php artisan config:cache
php artisan route:cache || true
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT}"