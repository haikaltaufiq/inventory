# Stage 1: Build Frontend Assets
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Production FrankenPHP Image
FROM dunglas/frankenphp:latest-php8.3-alpine

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
    git \
    unzip \
    zip \
    curl \
    netcat-openbsd \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy dependency definition & install vendor (Layer Caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-scripts --no-autoloader --no-interaction

# Copy sisa seluruh project
COPY . .

# Copy hasil build asset dari Stage 1
COPY --from=frontend-builder /app/public/build ./public/build

# Finish Composer Autoload
RUN composer dump-autoload --optimize --no-dev

# Set permission
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 8080

CMD ["sh", "-c", "\
  while ! nc -z \"$DB_HOST\" \"$DB_PORT\" > /dev/null 2>&1; do \
    sleep 2; \
  done && \
  php artisan migrate --force && \
  php artisan config:cache && \
  php artisan route:cache && \
  php artisan view:cache && \
  php artisan storage:link && \
  php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8080 \
"]