#!/usr/bin/env bash
# ==============================================================================
# ClearStay - Zero-Downtime Production Deployment Script (Ubuntu / AWS EC2)
# ==============================================================================
set -e

APP_DIR="/var/www/clearstay"
BRANCH="main"

echo "🚀 [1/8] Starting ClearStay deployment on $(date)..."
cd "$APP_DIR" || exit 1

# Activate maintenance mode with 15-second grace period
echo "🔒 [2/8] Entering maintenance mode..."
php artisan down --secret="clearstay-deploy-override" --render="errors::503" || true

# Pull latest code from GitHub
echo "📥 [3/8] Fetching latest release from Git repository ($BRANCH)..."
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

# Install PHP dependencies
echo "📦 [4/8] Installing Composer dependencies (no-dev, optimized)..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Run database migrations with force flag
echo "🗄️ [5/8] Running database schema migrations..."
php artisan migrate --force

# Optimize Laravel cache (config, routes, views, events)
echo "⚡ [6/8] Caching application routes, configs, and Blade views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart Background Queue Workers
echo "🔄 [7/8] Restarting Supervisor queue workers..."
php artisan queue:restart
if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart clearstay-worker:*
fi

# Reload PHP-FPM without terminating active requests
if command -v systemctl &> /dev/null; then
    echo "🌐 Reloading PHP-FPM..."
    sudo systemctl reload php8.3-fpm
fi

# Bring application back online
echo "✨ [8/8] Deactivating maintenance mode. ClearStay is live!"
php artisan up

echo "✅ ClearStay deployment completed successfully at $(date)!"
