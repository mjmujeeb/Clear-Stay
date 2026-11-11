
#!/bin/sh

# ============================================================
# CLEARSTAY 4.1 ENTERPRISE
# PRODUCTION DOCKER ENTRYPOINT
#
# Platform: Render / Railway / Fly.io / AWS ECS
# Framework: Laravel
# Runtime: PHP 8.3-FPM + Nginx + Supervisor
# Database: SQLite / MySQL / PostgreSQL
# ============================================================

set -eu

# ------------------------------------------------------------
# 1. APPLICATION DIRECTORY
# ------------------------------------------------------------

cd /var/www/html

echo "================================================"
echo " CLEARSTAY 4.1 ENTERPRISE INITIALIZATION"
echo "================================================"

# ------------------------------------------------------------
# 2. VERIFY APPLICATION ENCRYPTION KEY
# ------------------------------------------------------------

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not configured."
    echo "Configure APP_KEY in Render environment variables."
    exit 1
fi

echo "Application encryption key detected."

# ------------------------------------------------------------
# 3. DATABASE CONFIGURATION
# ------------------------------------------------------------

DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export DB_CONNECTION

if [ "$DB_CONNECTION" = "sqlite" ]; then

    DB_PATH="${DB_DATABASE:-/var/www/html/database/database.sqlite}"

    # Convert relative database paths to absolute paths.

    case "$DB_PATH" in
        /*)
            ;;
        *)
            DB_PATH="/var/www/html/$DB_PATH"
            ;;
    esac

    # Export normalized database path for Laravel.

    DB_DATABASE="$DB_PATH"
    export DB_DATABASE

    echo "Preparing SQLite database: $DB_PATH"

    mkdir -p "$(dirname "$DB_PATH")"

    if [ ! -f "$DB_PATH" ]; then
        echo "Creating SQLite database file..."
        touch "$DB_PATH"
    fi

    chown www-data:www-data "$DB_PATH"
    chmod 664 "$DB_PATH"

    chown www-data:www-data "$(dirname "$DB_PATH")"

    echo "SQLite database ready."

fi

# ------------------------------------------------------------
# 4. PREPARE LARAVEL STORAGE
# ------------------------------------------------------------

echo "Preparing Laravel storage directories..."

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data \
    storage \
    bootstrap/cache

chmod -R 775 \
    storage \
    bootstrap/cache

echo "Laravel storage permissions configured."

# ------------------------------------------------------------
# 5. RUN DATABASE MIGRATIONS
# ------------------------------------------------------------

echo "================================================"
echo " EXECUTING DATABASE MIGRATIONS"
echo "================================================"

php artisan migrate --force --no-interaction

echo "Database migrations completed successfully."

# ------------------------------------------------------------
# 6. OPTIONAL DEMONSTRATION DATA SEEDING
# ------------------------------------------------------------

# Demo seeders are disabled by default.
#
# Set RUN_DEMO_SEEDERS=true only when initializing
# a fresh demonstration database.
#
# Do not enable this on an existing production database
# unless all seeders are verified as safe to rerun.

if [ "${RUN_DEMO_SEEDERS:-false}" = "true" ]; then

    echo "================================================"
    echo " INITIALIZING DEMONSTRATION DATA"
    echo "================================================"

    php artisan db:seed --force --no-interaction

    echo "Demonstration data initialized successfully."

else

    echo "Automatic demonstration seeding skipped."

fi

# ------------------------------------------------------------
# 7. VERIFY STORAGE PERMISSIONS
# ------------------------------------------------------------

echo "Verifying Laravel storage permissions..."

chown -R www-data:www-data \
    storage \
    bootstrap/cache

# ------------------------------------------------------------
# 8. VERIFY STARTUP COMMAND
# ------------------------------------------------------------

if [ "$#" -eq 0 ]; then
    echo "ERROR: No application startup command supplied."
    exit 1
fi

echo "Startup command configured."

# ------------------------------------------------------------
# 9. START APPLICATION SERVICES
# ------------------------------------------------------------

echo "================================================"
echo " CLEARSTAY INITIALIZATION COMPLETE"
echo "================================================"

echo "Starting PHP-FPM and Nginx through Supervisor..."

# Replace entrypoint process with Supervisor.

exec "$@"
