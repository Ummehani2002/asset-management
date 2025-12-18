#!/bin/bash

# Tanseeq Asset Management - Deployment Script
# Run this script on your production server

echo "🚀 Starting deployment..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found! Please create it from .env.example"
    exit 1
fi

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Build assets
echo "🎨 Building assets..."
npm install
npm run build

# Generate app key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Create storage link
echo "📁 Creating storage symlink..."
php artisan storage:link

# Clear and cache config
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo "✅ Deployment complete!"
echo "📝 Don't forget to:"
echo "   1. Setup cron job: * * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"
echo "   2. Configure your web server (Nginx/Apache)"
echo "   3. Setup SSL certificate"
echo "   4. Test the application"

