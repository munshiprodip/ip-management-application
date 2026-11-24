# KYAMCH Smart IPAM — Production Deployment & Server Operations Guide

> **Official Repository:** `https://github.com/munshiprodip/ip-management-application.git`  
> **Maintainer:** Prodip Munshi (`munshiprodip@gmail.com`)  
> **Target Platform:** Ubuntu 22.04 / 24.04 LTS (or Debian 12 / RHEL 9)  
> **Application Stack:** Laravel 13, PHP 8.3+, Livewire 4, Tailwind CSS 4, Vite, MySQL 8.0+ InnoDB, Nginx, Supervisor, Database Queue  
> **Status:** Production-Ready, 147 Automated Tests Passing (100% Pass Rate)

---

## Table of Contents
1. [GitHub Repository & Local Push](#1-github-repository--local-push)
2. [Production Server Prerequisites](#2-production-server-prerequisites)
3. [Step-by-Step Server Setup](#3-step-by-step-server-setup)
   - [Step 1: Install System Packages](#step-1-install-system-packages)
   - [Step 2: Configure MySQL Database](#step-2-configure-mysql-database)
   - [Step 3: Clone Repository from GitHub](#step-3-clone-repository-from-github)
   - [Step 4: Configure Production Environment (.env)](#step-4-configure-production-environment-env)
   - [Step 5: Install Dependencies & Build Frontend](#step-5-install-dependencies--build-frontend)
   - [Step 6: Run Database Migrations & Seeders](#step-6-run-database-migrations--seeders)
   - [Step 7: Configure Directory Permissions](#step-7-configure-directory-permissions)
   - [Step 8: Production Performance Caching](#step-8-production-performance-caching)
   - [Step 9: Configure Nginx Web Server](#step-9-configure-nginx-web-server)
   - [Step 10: Install SSL Certificate (Let's Encrypt / Certbot)](#step-10-install-ssl-certificate-lets-encrypt--certbot)
   - [Step 11: Setup Queue Worker with Supervisor](#step-11-setup-queue-worker-with-supervisor)
   - [Step 12: Configure Laravel Scheduler (Cron)](#step-12-configure-laravel-scheduler-cron)
4. [Role & Access Governance Verification](#4-role--access-governance-verification)
5. [Zero-Downtime Deployment Script (CI/CD / Updates)](#5-zero-downtime-deployment-script-cicd--updates)
6. [Security & Hospital Network Hardening](#6-security--hospital-network-hardening)

---

## 1. GitHub Repository & Local Push

The local repository is linked and tracking the GitHub remote:

- **Repository URL:** `https://github.com/munshiprodip/ip-management-application.git`
- **Default Branch:** `main`

### 1.1 Local Git Identity Configuration
```bash
git config user.name "Prodip Munshi"
git config user.email "munshiprodip@gmail.com"
```

### 1.2 Pushing Updates to GitHub
```bash
# Push latest commits to main
git push origin main
```

---

## 2. Production Server Prerequisites

Ensure your production server meets these minimum hardware and software standards:

- **Server:** Minimum 2 vCPU, 4GB RAM, 20GB+ NVMe SSD.
- **OS:** Ubuntu 22.04 LTS / 24.04 LTS (recommended) or Debian 12.
- **PHP:** PHP 8.3 or 8.4 with FPM and core extensions (`bcmath`, `curl`, `mbstring`, `mysql`, `xml`, `zip`, `intl`, `posix`).
- **Database:** MySQL 8.0+ (or MariaDB 10.11+) with InnoDB storage engine.
- **Web Server:** Nginx 1.20+.
- **Process Manager:** Supervisor (manages background queue workers for network scanning).
- **Node.js:** Node.js 20 LTS + npm (for Vite frontend compilation).
- **Network Permissions:** ICMP echo capability (`ping`) enabled on the web/queue worker host.

---

## 3. Step-by-Step Server Setup

### Step 1: Install System Packages

SSH into your production server as a `sudo` user:

```bash
sudo apt update && sudo apt upgrade -y

# Install essential tools
sudo apt install -y software-properties-common curl git unzip zip supervisor certbot python3-certbot-nginx

# Add Ondřej Surý PHP PPA for PHP 8.3 / 8.4
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.4 and required extensions
sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-common php8.4-mysql php8.4-xml \
    php8.4-xmlrpc php8.4-curl php8.4-gd php8.4-imagick php8.4-mbstring \
    php8.4-opcache php8.4-soap php8.4-zip php8.4-bcmath php8.4-intl

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Node.js 20 LTS & npm
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Nginx and MySQL Server
sudo apt install -y nginx mysql-server
```

---

### Step 2: Configure MySQL Database

Run the secure installation wizard:

```bash
sudo mysql_secure_installation
```

Log in to MySQL and create the dedicated database and user:

```sql
sudo mysql -u root -p

-- Inside MySQL prompt:
CREATE DATABASE kyamch_ipam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'kyamch_user'@'localhost' IDENTIFIED BY 'Set_Your_Strong_Password_Here_!2026';

GRANT ALL PRIVILEGES ON kyamch_ipam.* TO 'kyamch_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

### Step 3: Clone Repository from GitHub

Clone into `/var/www/kyamch-ipam`:

```bash
# Clone the repository
sudo git clone https://github.com/munshiprodip/ip-management-application.git /var/www/kyamch-ipam

# Change directory
cd /var/www/kyamch-ipam
```

---

### Step 4: Configure Production Environment (.env)

Copy `.env.example` to `.env`:

```bash
sudo cp .env.example .env
sudo nano .env
```

Configure these critical production parameters:

```ini
APP_NAME="KYAMCH Smart IPAM"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Asia/Dhaka
APP_URL=https://ipam.kyamch.edu.bd

# Production MySQL Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kyamch_ipam
DB_USERNAME=kyamch_user
DB_PASSWORD=Set_Your_Strong_Password_Here_!2026

# Session, Cache & Queue (database driven for zero-dependency reliability)
SESSION_DRIVER=database
SESSION_LIFETIME=120
QUEUE_CONNECTION=database
CACHE_STORE=database

# Mandatory Hospital Safety Flags
IPAM_DISCOVERY_ENABLED=false
IPAM_EXTERNAL_WRITES_ENABLED=false
IPAM_DEFAULT_TIMEZONE=Asia/Dhaka
```

Generate the unique application encryption key:

```bash
php artisan key:generate
```

---

### Step 5: Install Dependencies & Build Frontend

```bash
# Install PHP dependencies without dev packages
composer install --no-dev --optimize-autoloader

# Install npm packages and build production assets with Vite
npm ci
npm run build
```

---

### Step 6: Run Database Migrations & Seeders

```bash
# Execute migrations safely
php artisan migrate --force

# Seed initial campus, baseline subnets, VLANs, and administrative accounts
php artisan db:seed --force
```

> **Default Seeded Accounts:**
> - **Super Admin:** `admin@kyamch.local` (Password: `Password123!`)
> - **Admin:** `netadmin@kyamch.local` (Password: `Password123!`)
> - **User:** `operator@kyamch.local` (Password: `Password123!`)
>
> *Immediately sign in and change these passwords from the User Management page.*

---

### Step 7: Configure Directory Permissions

Ensure the web server user (`www-data`) owns and can write to storage and bootstrap cache:

```bash
sudo chown -R www-data:www-data /var/www/kyamch-ipam
sudo find /var/www/kyamch-ipam -type f -exec chmod 644 {} \;
sudo find /var/www/kyamch-ipam -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/kyamch-ipam/storage
sudo chmod -R 775 /var/www/kyamch-ipam/bootstrap/cache
```

---

### Step 8: Production Performance Caching

Optimize configuration, routes, events, and views for maximum production speed:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

### Step 9: Configure Nginx Web Server

Create a new Nginx server block configuration:

```bash
sudo nano /etc/nginx/sites-available/kyamch-ipam.conf
```

Paste the following production configuration (replace `ipam.kyamch.edu.bd` with your domain or server IP):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name ipam.kyamch.edu.bd;
    root /var/www/kyamch-ipam/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;

    charset utf-8;

    # Maximum file upload size
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 60;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static asset caching
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, no-transform";
    }
}
```

Enable the site and verify Nginx syntax:

```bash
sudo ln -s /etc/nginx/sites-available/kyamch-ipam.conf /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

### Step 10: Install SSL Certificate (Let's Encrypt / Certbot)

Run Certbot to automatically provision and configure an SSL certificate:

```bash
sudo certbot --nginx -d ipam.kyamch.edu.bd
```

Certbot will automatically install SSL and redirect HTTP traffic to HTTPS.

---

### Step 11: Setup Queue Worker with Supervisor

Asynchronous tasks (such as background network scanning and bulk discovery jobs) require active queue workers.

Create a Supervisor worker configuration:

```bash
sudo nano /etc/supervisor/conf.d/kyamch-worker.conf
```

Paste:

```ini
[program:kyamch-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/kyamch-ipam/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/kyamch-ipam/storage/logs/worker.log
stopwaitsecs=3600
```

Start Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start kyamch-worker:*
```

Check status:

```bash
sudo supervisorctl status
```

---

### Step 12: Configure Laravel Scheduler (Cron)

Laravel schedules background housekeeping and log rotation via a single system cron entry.

Open crontab:

```bash
sudo crontab -u www-data -e
```

Add this line:

```cron
* * * * * cd /var/www/kyamch-ipam && php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Role & Access Governance Verification

Verify the three-tier security access model immediately following deployment:

1. **Super Admin Access (`admin@kyamch.local`):**
   - Access **User Management** at `https://ipam.kyamch.edu.bd/users`.
   - Access **Role & Permission Governance** at `https://ipam.kyamch.edu.bd/roles`.
   - Has full root authority to create, edit, deactivate, and assign any role (`Super Admin`, `Admin`, `User`).

2. **Admin Access (`netadmin@kyamch.local`):**
   - Can access **User Management** to create/edit Admin and User accounts.
   - Strictly blocked from assigning or creating Super Admin accounts.
   - Strictly blocked from editing, deactivating, or deleting Super Admin accounts.
   - Blocked with `403 Forbidden` from accessing `/roles`.

3. **User Access (`operator@kyamch.local`):**
   - Full operational control over core IPAM features: Dashboard, Subnets & CIDR Hierarchy, 802.1Q VLANs, IP Explorer & Allocations, ICMP Ping reachability, Network Scanner discovery, Campuses & Sites, and Host Types.
   - Blocked with `403 Forbidden` from both `/users` and `/roles`.

---

## 5. Zero-Downtime Deployment Script (CI/CD / Updates)

When deploying future updates from GitHub, use this automated script. Save it on your server as `/var/www/kyamch-ipam/deploy.sh`:

```bash
#!/bin/bash
set -e

echo "Deploying KYAMCH Smart IPAM..."

cd /var/www/kyamch-ipam

# Enter maintenance mode
php artisan down --retry=60 || true

# Pull latest code from GitHub
git pull origin main

# Install PHP dependencies
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Run database migrations
php artisan migrate --force

# Rebuild frontend assets
npm ci
npm run build

# Clear and rebuild production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart queue workers to reload new code
php artisan queue:restart
sudo supervisorctl restart kyamch-worker:*

# Exit maintenance mode
php artisan up

echo "Deployment finished successfully!"
```

Make it executable:

```bash
chmod +x /var/www/kyamch-ipam/deploy.sh
```

To deploy future updates:

```bash
sudo /var/www/kyamch-ipam/deploy.sh
```

---

## 6. Security & Hospital Network Hardening

1. **Firewall (UFW):**
   ```bash
   sudo ufw allow OpenSSH
   sudo ufw allow 'Nginx Full'
   sudo ufw enable
   ```
2. **Database Binding:** In `/etc/mysql/mysql.conf.d/mysqld.cnf`, verify `bind-address = 127.0.0.1` so MySQL is unreachable from external networks.
3. **Environment Security:** Ensure `.env` is set to `chmod 600 /var/www/kyamch-ipam/.env` owned by `www-data:www-data`.
4. **Regular Backups:** Configure automated daily backups of MySQL (`mysqldump`) and `.env` to off-site storage.
