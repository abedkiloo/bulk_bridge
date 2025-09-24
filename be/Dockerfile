# Simple Laravel server Dockerfile
FROM php:8.2-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    libzip-dev \
    postgresql-dev \
    redis

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies (without scripts first)
RUN composer install --no-scripts --no-autoloader --no-interaction

# Copy application code
COPY . .

# Create .env file if it doesn't exist
RUN cp .env.example .env || true

# Run composer autoloader
RUN composer dump-autoload --optimize --no-interaction

# Generate app key
RUN php artisan key:generate --no-interaction || true

# Set permissions
RUN chmod -R 755 storage bootstrap/cache

# Create storage directories
RUN mkdir -p storage/app/public \
    && mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs

# Expose port
EXPOSE 8000

# Start Laravel development server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]