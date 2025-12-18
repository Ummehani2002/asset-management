FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    zip \
    unzip

RUN docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Create cache directory
RUN mkdir -p bootstrap/cache && chmod 777 bootstrap/cache

COPY . .

# FIXED: Added --no-scripts flag
RUN composer install --no-dev --optimize-autoloader --no-scripts

EXPOSE 8000

# FIXED: Correct CMD syntax
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
