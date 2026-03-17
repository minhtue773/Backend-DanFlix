FROM php:8.2-cli

# Cài extensions
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip \
    && docker-php-ext-install pdo pdo_mysql zip

# Cài composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy code
COPY . .

# Install Laravel
RUN composer install

# Serve app
CMD php artisan serve --host=0.0.0.0 --port=10000