#!/usr/bin/env bash
# ==============================================================================
# ClearStay - Automated Database & Assets Backup Script
# Performs MySQL dump, compresses logs/uploads, pushes to AWS S3, & prunes old
# ==============================================================================
set -e

BACKUP_DIR="/var/backups/clearstay"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
ENV_FILE="/var/www/clearstay/.env"
RETENTION_DAYS=7

# Load DB credentials from .env
if [ -f "$ENV_FILE" ]; then
    DB_HOST=$(grep -E '^DB_HOST=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '"\r')
    DB_PORT=$(grep -E '^DB_PORT=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '"\r')
    DB_DATABASE=$(grep -E '^DB_DATABASE=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '"\r')
    DB_USERNAME=$(grep -E '^DB_USERNAME=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '"\r')
    DB_PASSWORD=$(grep -E '^DB_PASSWORD=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '"\r')
    AWS_BUCKET=$(grep -E '^AWS_BACKUP_BUCKET=' "$ENV_FILE" | cut -d '=' -f2 | tr -d '"\r')
fi

# Fallback defaults
DB_HOST=${DB_HOST:-"127.0.0.1"}
DB_PORT=${DB_PORT:-"3306"}
DB_DATABASE=${DB_DATABASE:-"clearstay_production"}
DB_USERNAME=${DB_USERNAME:-"clearstay_user"}

mkdir -p "$BACKUP_DIR"

echo "🗄️ [1/4] Starting MySQL backup for database '${DB_DATABASE}'..."
SQL_DUMP_FILE="${BACKUP_DIR}/db_${DB_DATABASE}_${TIMESTAMP}.sql.gz"

if [ -n "$DB_PASSWORD" ]; then
    mysqldump --single-transaction --quick --lock-tables=false \
        -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
        | gzip > "$SQL_DUMP_FILE"
else
    mysqldump --single-transaction --quick --lock-tables=false \
        -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" \
        | gzip > "$SQL_DUMP_FILE"
fi

echo "✅ Database dumped to: $SQL_DUMP_FILE"

# Backup application uploads & environment
echo "📦 [2/4] Archiving storage uploads..."
UPLOADS_ARCHIVE="${BACKUP_DIR}/uploads_${TIMESTAMP}.tar.gz"
if [ -d "/var/www/clearstay/storage/app/public" ]; then
    tar -czf "$UPLOADS_ARCHIVE" -C "/var/www/clearstay/storage/app" public
fi

# Optional AWS S3 Sync
if [ -n "$AWS_BUCKET" ] && command -v aws &> /dev/null; then
    echo "☁️ [3/4] Syncing backup archive to AWS S3 (s3://${AWS_BUCKET}/backups/)..."
    aws s3 cp "$SQL_DUMP_FILE" "s3://${AWS_BUCKET}/backups/db_${DB_DATABASE}_${TIMESTAMP}.sql.gz"
    if [ -f "$UPLOADS_ARCHIVE" ]; then
        aws s3 cp "$UPLOADS_ARCHIVE" "s3://${AWS_BUCKET}/backups/uploads_${TIMESTAMP}.tar.gz"
    fi
fi

# Retention policy: Prune local backups older than 7 days
echo "🧹 [4/4] Pruning local backups older than ${RETENTION_DAYS} days..."
find "$BACKUP_DIR" -type f -name "*.gz" -mtime +${RETENTION_DAYS} -delete

echo "🎉 Backup workflow finished at $(date)!"
