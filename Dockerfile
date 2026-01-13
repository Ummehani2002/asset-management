FROM php:8.2-cli

RUN apt-get update && apt-get install -y libpq-dev zip unzip
RUN docker-php-ext-install pdo pdo_pgsql
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# CRITICAL: Create Laravel directories
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache
RUN chmod -R 777 storage bootstrap/cache

COPY . .

# Set ownership
RUN chown -R www-data:www-data storage bootstrap/cache

# Create .env file from environment variables
RUN if [ ! -f .env ]; then \
    echo "APP_NAME=Laravel" >> .env; \
    echo "APP_ENV=local" >> .env; \
    echo "APP_DEBUG=true" >> .env; \
    echo "APP_KEY=${APP_KEY}" >> .env; \
    echo "APP_URL=${APP_URL}" >> .env; \
    echo "DB_CONNECTION=pgsql" >> .env; \
    echo "DB_HOST=${DB_HOST}" >> .env; \
    echo "DB_PORT=${DB_PORT}" >> .env; \
    echo "DB_DATABASE=${DB_DATABASE}" >> .env; \
    echo "DB_USERNAME=${DB_USERNAME}" >> .env; \
    echo "DB_PASSWORD=${DB_PASSWORD}" >> .env; \
    fi

RUN composer install --no-dev --optimize-autoloader --no-scripts

# Initialize Laravel caches
RUN php artisan config:clear
RUN php artisan cache:clear
RUN php artisan view:clear
RUN php artisan config:cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

