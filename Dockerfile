FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM php:8.2-apache

# Install runtime dependencies, including pg_dump for PostgreSQL backups.
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    postgresql-client \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions.
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Install Composer.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files and the already-built frontend assets.
COPY . .
COPY --from=frontend /app/public/build ./public/build
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Install production PHP dependencies.
RUN APP_ENV=build FILESYSTEM_PRIVATE_DISK=local FILESYSTEM_PUBLIC_DISK=public \
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Set permissions.
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

EXPOSE 80

RUN a2enmod rewrite

CMD ["apache2-foreground"]
