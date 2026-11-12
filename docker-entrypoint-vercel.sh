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
echo "Verifying application encryption key..."
if ! CLEAN_APP_KEY=$(php devops/scripts/validate-app-key.php); then
    echo "============================================================" >&2
    echo " FATAL: ClearStay container startup aborted due to APP_KEY."  >&2
    echo "============================================================" >&2
    exit 1
fi

export APP_KEY="$CLEAN_APP_KEY"

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

# If pgsql is requested but no external connection URL/Host is supplied in the
# Vercel environment, fall back to SQLite to prevent a container crash on 127.0.0.1.
if [ "$DB_CONNECTION" = "pgsql" ] && [ -z "${DATABASE_URL:-}" ] && [ -z "${DB_URL:-}" ] && [ -z "${POSTGRES_URL:-}" ] && [ -z "${DB_HOST:-}" ]; then
    echo "=========================================================="
    echo " Notice: DB_CONNECTION=pgsql but no DATABASE_URL was found."
    echo " Initializing local SQLite fallback to keep service online."
    echo " Add DATABASE_URL to Vercel Project Settings to use Neon."
    echo "=========================================================="
    DB_CONNECTION="sqlite"
fi

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
echo "Bootstrapping initial administrator if not present..."
php artisan clearstay:init-admin --force --no-interaction --reset-password || true

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
if [ "$DB_CONNECTION" = "sqlite" ] && [ -n "${DB_PATH:-}" ]; then
    chown -R www-data:www-data "$(dirname "$DB_PATH")" 2>/dev/null || true
    chmod -R 775 "$(dirname "$DB_PATH")" 2>/dev/null || true
fi

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
