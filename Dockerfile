# ============================================================
# Stage 1 – Builder: compile frontend assets
# ============================================================
FROM node:20-alpine AS builder

WORKDIR /app

# Install Node dependencies first (layer cache)
COPY package*.json ./
RUN npm ci

# Copy source and build Vite/Tailwind assets
COPY . .
RUN npm run build

# ============================================================
# Stage 2 – Production: PHP-FPM + nginx + supervisord
# ============================================================
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    netcat-openbsd \
    nginx \
    supervisor \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Install PHP dependencies (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction

# Copy full application source
COPY . .

# Copy Vite-compiled assets from builder stage
COPY --from=builder /app/public/build ./public/build

# Finalise Composer autoloader
RUN composer dump-autoload --optimize

# Set permissions
RUN chmod -R 775 storage bootstrap/cache public \
    && chown -R www-data:www-data /var/www

# ── nginx configuration ──────────────────────────────────────
RUN rm -f /etc/nginx/sites-enabled/default
COPY docker/nginx.conf /etc/nginx/sites-enabled/app.conf

# ── supervisord configuration ────────────────────────────────
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ── startup script ───────────────────────────────────────────
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 8080
CMD ["/usr/local/bin/start.sh"]