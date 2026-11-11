
# ============================================================
# CLEARSTAY 4.1 ENTERPRISE - PRODUCTION DOCKERFILE
# Render / Railway / Fly.io / AWS ECS / Google Cloud Run
# PHP 8.3 | Laravel | Nginx | PHP-FPM | Supervisor
# ============================================================

FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

# ------------------------------------------------------------
# 1. INSTALL SYSTEM DEPENDENCIES
# ------------------------------------------------------------

RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    sqlite-dev \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS

# ------------------------------------------------------------
# 2. INSTALL PHP EXTENSIONS
# ------------------------------------------------------------

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        pdo \
        pdo_sqlite \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        zip \
        bcmath \
        intl \
        opcache \
        pcntl

# Verify PHP and PHP-FPM

RUN php -v \
    && php-fpm -v \
    && test -x /usr/local/sbin/php-fpm

# ------------------------------------------------------------
# 3. INSTALL COMPOSER
# ------------------------------------------------------------

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ------------------------------------------------------------
# 4. COPY APPLICATION
# ------------------------------------------------------------

COPY . /var/www/html

# ------------------------------------------------------------
# 5. INSTALL PRODUCTION DEPENDENCIES
# ------------------------------------------------------------

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

# ------------------------------------------------------------
# 6. PREPARE LARAVEL DIRECTORIES
# ------------------------------------------------------------

RUN mkdir -p \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    /var/www/html/database \
    /run/nginx \
    /var/log/supervisor \
    && chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
        /var/www/html/database \
    && chmod -R 775 \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
        /var/www/html/database

# ------------------------------------------------------------
# 7. PRODUCTION OPCACHE
# ------------------------------------------------------------

RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# ------------------------------------------------------------
# 8. NGINX CONFIGURATION
# ------------------------------------------------------------

RUN cat > /etc/nginx/http.d/default.conf <<'NGINX'
server {
    listen 8080 default_server;
    server_name _;

    root /var/www/html/public;
    index index.php index.html;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }

    location = /robots.txt {
        access_log off;
        log_not_found off;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

# ------------------------------------------------------------
# 9. SUPERVISOR CONFIGURATION
# ------------------------------------------------------------

RUN cat > /etc/supervisord.conf <<'SUPERVISOR'
[supervisord]
nodaemon=true
user=root
logfile=/dev/null
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=/usr/local/sbin/php-fpm -F
directory=/var/www/html
autostart=true
autorestart=true
startsecs=2
priority=10
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=/usr/sbin/nginx -g "daemon off;"
directory=/var/www/html
autostart=true
autorestart=true
startsecs=2
priority=20
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
SUPERVISOR

# ------------------------------------------------------------
# 10. COPY ENTRYPOINT
# ------------------------------------------------------------

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# ------------------------------------------------------------
# 11. EXPOSE APPLICATION PORT
# ------------------------------------------------------------

EXPOSE 8080

# ------------------------------------------------------------
# 12. HEALTH CHECK
# ------------------------------------------------------------

HEALTHCHECK \
    --interval=30s \
    --timeout=5s \
    --start-period=60s \
    --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/up || exit 1

# ------------------------------------------------------------
# 13. START APPLICATION
# ------------------------------------------------------------

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
