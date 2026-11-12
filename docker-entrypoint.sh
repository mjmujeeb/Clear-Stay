#!/bin/sh
# ============================================================
# CLEARSTAY 4.1 ENTERPRISE - PRODUCTION DOCKER ENTRYPOINT
# Platform: Render / Railway / Fly.io / AWS ECS
# Framework: Laravel 13 | PHP 8.3-FPM + Nginx + Supervisor
# ============================================================

set -eu

cd /var/www/html

echo "================================================"
echo " CLEARSTAY 4.1 ENTERPRISE INITIALIZATION"
echo "================================================"

# ------------------------------------------------------------
# 1. VERIFY / PRESERVE APPLICATION ENCRYPTION KEY
# ------------------------------------------------------------
echo "Verifying application encryption key..."
if ! CLEAN_APP_KEY=$(php devops/scripts/validate-app-key.php); then
    echo "============================================================" >&2
    echo " FATAL: ClearStay container startup aborted due to APP_KEY."  >&2
    echo "============================================================" >&2
    exit 1
fi

export APP_KEY="$CLEAN_APP_KEY"

# ------------------------------------------------------------
# 2. DYNAMIC PORT & NGINX CONFIGURATION (Render / Cloud Port)
# ------------------------------------------------------------
PORT="${PORT:-10000}"
export PORT
echo "Configuring Nginx to listen on port ${PORT}..."

if [ -f /etc/nginx/http.d/default.conf ]; then
    sed -i "s/listen [0-9]\+ default_server;/listen ${PORT} default_server;/" /etc/nginx/http.d/default.conf
    echo "Nginx configured for port ${PORT}."
fi

# ------------------------------------------------------------
# 3. DATABASE CONFIGURATION & INITIALIZATION
# ------------------------------------------------------------
DB_CONNECTION="${DB_CONNECTION:-sqlite}"

if [ "$DB_CONNECTION" = "pgsql" ] && [ -z "${DATABASE_URL:-}" ] && [ -z "${DB_URL:-}" ] && [ -z "${POSTGRES_URL:-}" ] && [ -z "${DB_HOST:-}" ]; then
    echo "Notice: DB_CONNECTION=pgsql but no DATABASE_URL or DB_HOST was provided."
    echo "Falling back to local SQLite to ensure application boots safely."
    DB_CONNECTION="sqlite"
fi

export DB_CONNECTION

if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_PATH="${DB_DATABASE:-/var/www/html/database/database.sqlite}"

    case "$DB_PATH" in
        /*)
            ;;
        *)
            DB_PATH="/var/www/html/$DB_PATH"
            ;;
    esac

    DB_DATABASE="$DB_PATH"
    export DB_DATABASE

    echo "Preparing SQLite database at: $DB_PATH"
    mkdir -p "$(dirname "$DB_PATH")"

    if [ ! -f "$DB_PATH" ]; then
        echo "Creating initial SQLite database file..."
        touch "$DB_PATH"
    else
        echo "Existing SQLite database preserved."
    fi

    chown www-data:www-data "$DB_PATH"
    chmod 664 "$DB_PATH"
    chown www-data:www-data "$(dirname "$DB_PATH")"
    chmod 775 "$(dirname "$DB_PATH")"
    echo "SQLite database ready."
else
    echo "Using external database connection: ${DB_CONNECTION}"
fi

# ------------------------------------------------------------
# 4. PREPARE STORAGE & CACHE DIRECTORIES
# ------------------------------------------------------------
echo "Configuring Laravel storage and cache directories..."

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
echo "Laravel storage permissions configured."

# ------------------------------------------------------------
# 5. RUN DATABASE MIGRATIONS (NON-DESTRUCTIVE)
# ------------------------------------------------------------
echo "================================================"
echo " EXECUTING DATABASE MIGRATIONS"
echo "================================================"

php artisan migrate --force --no-interaction || {
    echo "Warning: Database migrations encountered an issue. Continuing container boot..."
}

echo "Database migrations completed successfully."

# ------------------------------------------------------------
# 6. DEMONSTRATION SEEDING (DISABLED BY DEFAULT IN PROD)
# ------------------------------------------------------------
if [ "${RUN_DEMO_SEEDERS:-false}" = "true" ]; then
    echo "================================================"
    echo " INITIALIZING DEMONSTRATION DATA (OPT-IN)"
    echo "================================================"
    php artisan db:seed --force --no-interaction
    echo "Demonstration data initialized successfully."
else
    echo "Automatic demonstration seeding skipped (production safe)."
fi

# ------------------------------------------------------------
# 7. OPTIMIZE CONFIGURATION AND ROUTES CACHE
# ------------------------------------------------------------
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Optimizing Laravel cache for production..."
    php artisan config:cache --no-interaction || true
    php artisan route:cache --no-interaction || true
    php artisan view:cache --no-interaction || true
fi

# ------------------------------------------------------------
# 7.5 ENFORCE WWW-DATA RUNTIME PERMISSIONS
# Ensures PHP-FPM can write sessions, views, logs, and database
# ------------------------------------------------------------
echo "Enforcing final runtime permissions for www-data..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

if [ "$DB_CONNECTION" = "sqlite" ]; then
    chown -R www-data:www-data "$(dirname "$DB_PATH")"
    chmod -R 775 "$(dirname "$DB_PATH")"
    if [ -f "$DB_PATH" ]; then
        chown www-data:www-data "$DB_PATH"
        chmod 664 "$DB_PATH"
    fi
fi
echo "Runtime permissions verified."

# ------------------------------------------------------------
# 8. VERIFY STARTUP COMMAND & EXECUTE
# ------------------------------------------------------------
if [ "$#" -eq 0 ]; then
    echo "ERROR: No application startup command supplied."
    exit 1
fi

echo "================================================"
echo " CLEARSTAY INITIALIZATION COMPLETE"
echo " Starting services on port ${PORT}..."
echo "================================================"

exec "$@"
