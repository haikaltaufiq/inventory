FROM laravelsail/php83-composer:latest

# Switch ke root untuk proses instalasi dependensi sistem
USER root

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    netcat-openbsd \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js 20 resmi dari NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copy manifest file untuk memanfaatkan caching layer Docker
COPY package*.json composer.json composer.lock ./

# Install dependensi backend dan frontend secara paralel/terpisah
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction \
    && npm ci

# Copy keseluruhan source code aplikasi
COPY . .

# Optimasi autoloader Composer dan compile aset Vite
RUN composer dump-autoload --optimize \
    && npm run build

# Atur permission directory storage, cache, dan public untuk web server
RUN chmod -R 775 storage bootstrap/cache public \
    && chown -R www-data:www-data /var/www

EXPOSE 8080
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
  echo '🚀 Menjalankan Laravel server...' && \
  php artisan serve --host=0.0.0.0 --port=$PORT \
"]