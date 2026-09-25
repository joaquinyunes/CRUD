# syntax=docker/dockerfile:1

# ---------- 1. Assets del front (Vite + Tailwind) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
RUN npm run build

# ---------- 2. Imagen final ----------
FROM dunglas/frankenphp:1-php8.3 AS app

# Extensiones que usa el sistema:
#   pdo_pgsql / pdo_mysql -> base de datos
#   gd                    -> PDFs con dompdf y phpspreadsheet
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

# Las dependencias se resuelven acá y no en la imagen oficial de Composer: esa
# imagen no trae gd ni bcmath, y varios paquetes (phpspreadsheet, laravel-lang)
# los exigen. Resolviendo en la imagen final, Composer valida contra el PHP que
# realmente va a ejecutar la aplicación.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

# Primero solo los manifiestos, para que la capa de vendor se cachee mientras no
# cambien las dependencias.
COPY composer.json composer.lock ./
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-interaction \
      --prefer-dist \
      --optimize-autoloader

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# El binario de FrankenPHP viene con la capability cap_net_bind_service para
# poder abrir el puerto 80. Varias plataformas (Render entre ellas) ejecutan el
# contenedor sin esa capability en el conjunto permitido, y entonces el propio
# exec del binario falla con "Operation not permitted" (exit 126). Como la
# aplicacion escucha en un puerto alto, la capability no hace falta.
RUN setcap -r /usr/local/bin/frankenphp || true

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENV SERVER_NAME=:8080
EXPOSE 8080

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
