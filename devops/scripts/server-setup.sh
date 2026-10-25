#!/usr/bin/env bash
# ==============================================================================
# ClearStay - Complete Ubuntu 22.04 / 24.04 Server Provisioning Script
# Installs: Nginx, PHP 8.3 FPM, Composer, Node 20, UFW, Fail2Ban, Supervisor, Certbot
# ==============================================================================
set -e

if [ "$EUID" -ne 0 ]; then
    echo "❌ Please run as root (sudo bash server-setup.sh)"
    exit 1
fi

echo "🚀 [1/9] Updating system packages..."
apt-get update && apt-get upgrade -y
apt-get install -y curl git unzip software-properties-common ufw fail2ban supervisor logrotate certbot python3-certbot-nginx

echo "🌐 [2/9] Installing Nginx..."
apt-get install -y nginx
systemctl enable nginx

echo "🐘 [3/9] Adding Ondrej PHP PPA and installing PHP 8.3 & extensions..."
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-sqlite3 php8.3-curl \
    php8.3-mbstring php8.3-xml php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath php8.3-opcache
systemctl enable php8.3-fpm

echo "📦 [4/9] Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "🟢 [5/9] Installing Node.js 20 LTS..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

echo "🔥 [6/9] Configuring UFW Firewall..."
ufw default deny incoming
ufw default allow outgoing
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

echo "🛡️ [7/9] Setting up ClearStay Fail2Ban & Logrotate..."
cp /var/www/clearstay/devops/fail2ban/clearstay.conf /etc/fail2ban/filter.d/clearstay.conf 2>/dev/null || true
cp /var/www/clearstay/devops/logrotate/clearstay /etc/logrotate.d/clearstay 2>/dev/null || true
systemctl restart fail2ban

echo "⚙️ [8/9] Configuring Supervisor queue worker daemon..."
cp /var/www/clearstay/devops/supervisor/clearstay-worker.conf /etc/supervisor/conf.d/clearstay-worker.conf 2>/dev/null || true
supervisorctl reread || true
supervisorctl update || true

echo "📁 [9/9] Configuring folder permissions..."
mkdir -p /var/www/clearstay
chown -R www-data:www-data /var/www/clearstay
chmod -R 775 /var/www/clearstay/storage /var/www/clearstay/bootstrap/cache 2>/dev/null || true

echo "=========================================================================="
echo "🎉 Server setup complete! Next steps:"
echo "1. Clone project into /var/www/clearstay"
echo "2. Copy /var/www/clearstay/devops/nginx/clearstay.conf to /etc/nginx/sites-available/"
echo "3. ln -s /etc/nginx/sites-available/clearstay.conf /etc/nginx/sites-enabled/"
echo "4. Run: certbot --nginx -d your-domain.com"
echo "=========================================================================="
