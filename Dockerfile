FROM php:8.3-cli

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    NODE_OPTIONS=--max_old_space_size=2048

RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libxml2-dev libonig-dev nodejs npm \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo pdo_mysql mbstring zip xml gd bcmath \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

COPY . .

RUN npm run build \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache \
 && chmod +x /var/www/html/entrypoint.sh

EXPOSE 8080

CMD ["/var/www/html/entrypoint.sh"]
