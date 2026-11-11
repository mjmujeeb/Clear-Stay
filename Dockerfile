
# ============================================================
# CLEARSTAY 4.1 ENTERPRISE - PRODUCTION DOCKERFILE
# Optimized for Render, Railway, Fly.io, AWS ECS and Cloud Run
# PHP 8.3 | Laravel | Nginx | PHP-FPM | Supervisor
# ============================================================

FROM php:8.3-fpm-alpine

# ------------------------------------------------------------
# 1. SET WORKING DIRECTORY
# ------------------------------------------------------------

WORKDIR /var/www/html

# ------------------------------------------------------------
# 2. INSTALL SYSTEM DEPENDENCIES
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
# 3. CONFIGURE AND INSTALL PHP EXTENSIONS
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

# Verify PHP and PHP-FPM executables exist
RUN php -v \
    && php-fpm -v \
    && test -x /usr/local/sbin/php-fpm

# ------------------------------------------------------------
# 4. INSTALL COMPOSER
# ------------------------------------------------------------

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ------------------------------------------------------------
# 5. COPY PROJECT FILES
# ------------------------------------------------------------

COPY . /var/www/html

# ------------------------------------------------------------
# 6. INSTALL PRODUCTION DEPENDENCIES
# ------------------------------------------------------------

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

# ------------------------------------------------------------
# 7. CONFIGURE LARAVEL DIRECTORIES AND PERMISSIONS
# ------------------------------------------------------------

RUN mkdir -p \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    /var/www/html/database \
    && chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
        /var/www/html/database \
    && chmod -R 775 \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
        /var/www/html/database

# ------------------------------------------------------------
# 8. CONFIGURE OPCACHE FOR PRODUCTION
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
# 9. CONFIGURE NGINX
# ------------------------------------------------------------

RUN { \
    echo 'server {'; \
    echo '    listen 8080 default_server;'; \
    echo '    server_name _;'; \
    echo '    root /var/www/html/public;'; \
    echo '    index index.php index.html;'; \
    echo '    charset utf-8;'; \
    echo ''; \
    echo '    location / {'; \
    echo '        try_files $uri $uri/ /index.php?$query_string;'; \
    echo '    }'; \
    echo ''; \
    echo '    location = /favicon.ico {'; \
    echo '        access_log off;'; \
    echo '        log_not_found off;'; \
    echo '    }'; \
    echo ''; \
    echo '    location = /robots.txt {'; \
    echo '        access_log off;'; \
    echo '        log_not_found off;'; \
    echo '    }'; \
    echo ''; \
    echo '    error_page 404 /index.php;'; \
    echo ''; \
    echo '    location ~ \.php$ {'; \
    echo '        try_files $uri =404;'; \
    echo '        fastcgi_pass 127.0.0.1:9000;'; \
    echo '        fastcgi_index index.php;'; \
    echo '        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;'; \
    echo '        include fastcgi_params;'; \
    echo '        fastcgi_hide_header X-Powered-By;'; \
    echo '    }'; \
    echo ''; \
    echo '    location ~ /\.(?!well-known).* {'; \
    echo '        deny all;'; \
    echo '    }'; \
    echo '}'; \
} > /etc/nginx/http.d/default.conf

# ------------------------------------------------------------
# 10. CONFIGURE SUPERVISOR
# ------------------------------------------------------------

RUN { \
    echo '[supervisord]'; \
    echo 'nodaemon=true'; \
    echo 'user=root'; \
    echo 'logfile=/dev/null'; \
    echo 'pidfile=/var/run/supervisord.pid'; \
    echo ''; \
    echo '[program:php-fpm]'; \
    echo 'command=/usr/local/sbin/php-fpm -F'; \
    echo 'directory=/var/www/html'; \
    echo 'autostart=true'; \
    echo 'autorestart=true'; \
    echo 'startsecs=2'; \
    echo 'priority=10'; \
    echo 'stdout_logfile=/dev/stdout'; \
    echo 'stdout_logfile_maxbytes=0'; \
    echo 'stderr_logfile=/dev/stderr'; \
    echo 'stderr_logfile_maxbytes=0'; \
    echo ''; \
    echo '[program:nginx]'; \
    echo 'command=/usr/sbin/nginx -g "daemon off;"'; \
    echo 'directory=/var/www/html'; \
    echo 'autostart=true'; \
    echo 'autorestart=true'; \
    echo 'startsecs=2'; \
    echo 'priority=20'; \
    echo 'stdout_logfile=/dev/stdout'; \
    echo 'stdout_logfile_maxbytes=0'; \
    echo 'stderr_logfile=/dev/stderr'; \
    echo 'stderr_logfile_maxbytes=0'; \
} > /etc/supervisord.conf

# ------------------------------------------------------------
# 11. CONFIGURE ENTRYPOINT
# ------------------------------------------------------------

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# ------------------------------------------------------------
# 12. EXPOSE APPLICATION PORT
# ------------------------------------------------------------

EXPOSE 8080

# ------------------------------------------------------------
# 13. HEALTH CHECK
# ------------------------------------------------------------

HEALTHCHECK \
    --interval=30s \
    --timeout=5s \
    --start-period=30s \
    --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/up || exit 1

# ------------------------------------------------------------
# 14. START APPLICATION
# ------------------------------------------------------------

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
