# ─── Stage 1: Build assets ───────────────────────────────────────────────────
FROM laravelsail/php83-composer:latest AS builder

# Install Node.js 20 and build dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY . .

# Install PHP and JS dependencies, compile Vite assets
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm install \
    && npm run build

# ─── Stage 2: Production image ───────────────────────────────────────────────
FROM php:8.3-fpm

# Install nginx, supervisor, and required PHP extensions
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git \
    unzip \
    zip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    netcat-openbsd \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copy application files from builder (including compiled assets)
COPY --from=builder /var/www .

# Copy nginx and supervisor configuration
COPY nginx.conf /etc/nginx/sites-available/default
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set correct permissions
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public

# Create supervisor log directory
RUN mkdir -p /var/log/supervisor

EXPOSE 8080

# Run migrations, cache commands, then start nginx + PHP-FPM via supervisor
CMD ["sh", "-c", "\
  echo 'Menunggu koneksi ke MySQL di $DB_HOST:$DB_PORT...' && \
  while ! nc -z \"$DB_HOST\" \"$DB_PORT\"; do \
    echo 'MySQL belum siap, menunggu...' && sleep 5; \
  done && \
  echo '✅ MySQL terkoneksi, lanjut migrasi...' && \
  php artisan migrate --seed --force || { echo '❌ Migrasi gagal!'; exit 1; } && \
  php artisan config:clear && \
  php artisan cache:clear && \
  php artisan config:cache && \
  php artisan route:cache && \
  php artisan view:cache && \
  php artisan storage:link && \
  echo '🚀 Menjalankan nginx + PHP-FPM via supervisor...' && \
  exec supervisord -c /etc/supervisor/conf.d/supervisord.conf \
"]
