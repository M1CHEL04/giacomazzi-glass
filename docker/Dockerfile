# ── Stage 1: Build Vite/Node assets ──────────────────────────────────────────
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# ── Stage 2: Application (PHP-FPM + Nginx via Supervisor) ────────────────────
FROM php:8.3-fpm

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        unzip \
        git \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        gd \
        zip \
        mbstring \
        bcmath \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Application code
COPY . .

# Compiled frontend assets from stage 1
COPY --from=node-builder /app/public/build ./public/build

# PHP and Laravel dependencies (production only)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Nginx site config
COPY docker/nginx/app.conf /etc/nginx/sites-available/default

# PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# Supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Permissions — www-data owns storage and cache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
