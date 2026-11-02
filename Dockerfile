FROM php:8.4-fpm-alpine AS app
RUN apk add --no-cache postgresql-dev libzip-dev icu-dev \
 && docker-php-ext-install pdo_pgsql opcache zip intl bcmath
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
RUN chown -R www-data:www-data storage bootstrap/cache

FROM nginx:alpine AS web
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public