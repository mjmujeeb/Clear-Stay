#!/bin/sh
# ============================================================
# CLEARSTAY 4.1 ENTERPRISE - VERCEL CONTAINER ENTRYPOINT
# Target Platform: Vercel Container Functions (Fluid Compute)
# Runtime: PHP 8.3-FPM + Nginx + Supervisor
# ============================================================

set -eu

cd /var/www/html

echo "================================================"
echo " CLEARSTAY 4.1 ENTERPRISE - VERCEL STARTUP"
echo "================================================"

# ------------------------------------------------------------
# 1. VERIFY / PRESERVE APPLICATION ENCRYPTION KEY
# ------------------------------------------------------------
if [ -z "${APP_KEY:-}" ]; then
    echo "Warning: APP_KEY not detected in Vercel environment."
    echo "Generating temporary application key..."
    php artisan key:generate --force --no-interaction
else
    echo "Application encryption key verified."
fi

# ------------------------------------------------------------
# 2. VERCEL DYNAMIC PORT & NGINX CONFIGURATION
# Vercel injects $PORT (defaults to 80 or 8080)
# ------------------------------------------------------------
PORT="${PORT:-80}"
export PORT
echo "Configuring Nginx for Vercel listener on port ${PORT}..."

if [ -f /etc/nginx/http.d/default.conf ]; then
    sed -i "s/listen [0-9]\+ default_server;/listen ${PORT} default_server;/" /etc/nginx/http.d/default.conf
    echo "Nginx port set to ${PORT}."
fi

# ------------------------------------------------------------
# 3. PREPARE STORAGE DIRECTORIES
# ------------------------------------------------------------
mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# ------------------------------------------------------------
# 4. DATABASE INITIALIZATION & MIGRATIONS
# Persistent PostgreSQL is recommended for Vercel
# ------------------------------------------------------------
DB_CONNECTION="${DB_CONNECTION:-pgsql}"
export DB_CONNECTION

echo "Configured database driver: ${DB_CONNECTION}"

if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_PATH="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    case "$DB_PATH" in
        /*) ;;
        *) DB_PATH="/var/www/html/$DB_PATH" ;;
    esac
    export DB_DATABASE="$DB_PATH"
    mkdir -p "$(dirname "$DB_PATH")"
    [ -f "$DB_PATH" ] || touch "$DB_PATH"
    chown www-data:www-data "$DB_PATH" "$(dirname "$DB_PATH")"
    chmod 664 "$DB_PATH"
    chmod 775 "$(dirname "$DB_PATH")"
fi

echo "================================================"
echo " EXECUTING NON-DESTRUCTIVE DATABASE MIGRATIONS"
echo "================================================"
php artisan migrate --force --no-interaction || {
    echo "Warning: Database migrations encountered an issue. Continuing container boot..."
}

# ------------------------------------------------------------
# 5. IDEMPOTENT FIRST-TIME ADMIN & TENANT BOOTSTRAP
# ------------------------------------------------------------
if [ -n "${ADMIN_EMAIL:-}" ]; then
    echo "Bootstrapping initial administrator if not present..."
    php artisan clearstay:init-admin --force --no-interaction || true
fi

# ------------------------------------------------------------
# 6. OPTIMIZE CONFIGURATION AND ROUTES CACHE
# ------------------------------------------------------------
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Optimizing Laravel cache for production..."
    php artisan config:cache --no-interaction || true
    php artisan route:cache --no-interaction || true
    php artisan view:cache --no-interaction || true
fi

# ------------------------------------------------------------
# 7. ENFORCE WWW-DATA RUNTIME PERMISSIONS
# Ensures PHP-FPM can write sessions, views, and logs without 500 errors
# ------------------------------------------------------------
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ------------------------------------------------------------
# 8. EXECUTE STARTUP SUPERVISOR
# ------------------------------------------------------------
if [ "$#" -eq 0 ]; then
    echo "ERROR: No startup command provided."
    exit 1
fi

echo "================================================"
echo " CLEARSTAY VERCEL CONTAINER READY"
echo " Listening on http://0.0.0.0:${PORT}"
echo "================================================"

exec "$@"
