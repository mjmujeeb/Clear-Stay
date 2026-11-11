#!/bin/sh
set -e

# Generate APP_KEY if not configured
if [ -z "$APP_KEY" ]; then
    echo "Generating application encryption key..."
    php artisan key:generate --force
fi

# Setup SQLite database if DB_CONNECTION is sqlite
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    DB_PATH=${DB_DATABASE:-/var/www/html/database/database.sqlite}
    mkdir -p "$(dirname "$DB_PATH")"
    if [ ! -f "$DB_PATH" ]; then
        echo "Creating SQLite database file at $DB_PATH..."
        touch "$DB_PATH"
        chown www-data:www-data "$DB_PATH"
        chmod 664 "$DB_PATH"
    fi
fi

# Run database migrations safely
echo "Executing database migrations..."
php artisan migrate --force --no-interaction

# Check if users table has data, seed if empty
USER_COUNT=$(php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class); \$kernel->bootstrap(); echo App\Models\User::count();" 2>/dev/null || echo "0")

if [ "$USER_COUNT" = "0" ]; then
    echo "Seeding initial demonstration users and hotel inventory..."
    php artisan db:seed --force --no-interaction 2>/dev/null || true
fi

# Ensure storage permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "ClearStay Enterprise container initialization complete. Starting services..."
exec "$@"
