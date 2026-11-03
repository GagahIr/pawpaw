# ---- Stage 1: dependency PHP ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize

# ---- Stage 2: build aset frontend (Vite) ----
FROM node:22 AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---- Stage 3: aplikasi (PHP-FPM) ----
FROM php:8.4-fpm-alpine AS app
RUN apk add --no-cache postgresql-dev libzip-dev icu-dev \
 && docker-php-ext-install pdo_pgsql opcache zip intl bcmath
RUN printf 'upload_max_filesize=20M\npost_max_size=25M\nmemory_limit=256M\n' > /usr/local/etc/php/conf.d/uploads.ini
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
RUN mkdir -p storage/app/public storage/app/livewire-tmp storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
 && chown -R www-data:www-data storage bootstrap/cache

# ---- Stage 4: web server ----
FROM nginx:alpine AS web
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
RUN ln -s /var/www/html/storage/app/public /var/www/html/public/storage