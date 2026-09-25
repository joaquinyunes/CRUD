# syntax=docker/dockerfile:1

# ---------- 1. Assets del front (Vite + Tailwind) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
RUN npm run build

# ---------- 2. Dependencias de PHP ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-interaction \
      --prefer-dist \
      --optimize-autoloader

# ---------- 3. Imagen final ----------
FROM dunglas/frankenphp:1-php8.3 AS app

# Extensiones que usa el sistema:
#   pdo_pgsql / pdo_mysql -> base de datos
#   gd                    -> PDFs con dompdf
#   zip                   -> exportación a Excel
#   bcmath, intl          -> cálculos de totales y formato de moneda
RUN install-php-extensions \
      pdo_pgsql \
      pdo_mysql \
      gd \
      zip \
      bcmath \
      intl \
      opcache

# La imagen de FrankenPHP no trae Composer: lo copiamos para regenerar el
# autoloader y correr package:discover con el codigo de la app presente.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENV SERVER_NAME=:8080
EXPOSE 8080

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
