# Deployment Guide

## Pre-Deployment Checklist

### Code Quality
- [ ] All tests pass: `php vendor/bin/phpunit`
- [ ] Code style fixed: `php vendor/bin/pint`
- [ ] No compiler warnings or errors
- [ ] Security dependencies up to date: `composer audit`

### Security Configuration
- [ ] `APP_KEY` is set once and kept stable across web, worker, and scheduler processes
- [ ] `APP_DEBUG` set to `false`
- [ ] `APP_ENV` set to `production`
- [ ] All sensitive environment variables configured
- [ ] CSRF tokens enabled
- [ ] CORS properly configured for frontend domain

### Database
- [ ] Database backup created
- [ ] Migrations tested on staging
- [ ] Database credentials rotated and secure
- [ ] Connection uses SSL/TLS where possible

### File Permissions
- [ ] `storage/` directory writable by web server
- [ ] `bootstrap/cache/` directory writable by web server
- [ ] Sensitive files not world-readable

### Assets & Frontend
- [ ] Frontend assets built: `npm run build`
- [ ] CSS and JS minified
- [ ] No source maps in production
- [ ] Images optimized

---

## Environment Configuration

### Production `.env` Template

```bash
# Application
APP_NAME="Clean Flow"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cleanflow.example.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

# Logging (Use Daily or Stack)
LOG_CHANNEL=daily
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

# Database (PostgreSQL recommended for production)
DB_CONNECTION=pgsql
DB_HOST=your-db-host.example.com
DB_PORT=5432
DB_DATABASE=cleanflow_prod
DB_USERNAME=cleanflow_user
DB_PASSWORD=your_secure_password_here
DB_SCHEMA=public
DB_SSLMODE=require

# Session & Cache (Use Redis for production)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
SESSION_PATH=/
SESSION_DOMAIN=.example.com

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=s3
FILESYSTEM_PRIVATE_DISK=s3
FILESYSTEM_PROOF_DISK=s3_proof
FILESYSTEM_PUBLIC_DISK=s3_public
DATABASE_BACKUP_DISK=s3
QUEUE_CONNECTION=database

# IoT devices must use the signed timestamp + nonce + HMAC protocol.
IOT_REQUIRE_SIGNED_REQUESTS=true

CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=your-redis-host.example.com
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# Mail (Configure with real SMTP service)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@cleanflow.example.com
MAIL_FROM_NAME="Clean Flow"

# Primary S3 storage (for the main private disk and database backups)
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=us-east-1
AWS_PRIVATE_BUCKET=your-private-bucket-name
AWS_PUBLIC_BUCKET=your-public-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false

# Cloudflare R2 (for booking proofs and public catalog media when the primary
# S3 credentials are managed by Laravel Cloud or another provider)
R2_ACCESS_KEY_ID=your_r2_access_key
R2_SECRET_ACCESS_KEY=your_r2_secret
R2_DEFAULT_REGION=auto
R2_PRIVATE_BUCKET=cleanflow-private
R2_PUBLIC_BUCKET=cleanflow-public
R2_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
R2_PUBLIC_URL=https://<PUBLIC_BUCKET_SUBDOMAIN>.r2.dev
R2_USE_PATH_STYLE_ENDPOINT=false

# Custom Settings
ATTENDANCE_TIMEZONE=Asia/Manila
VITE_APP_NAME="Clean Flow"
```

---

## Deployment Steps

### 1. Server Preparation

**Install System Dependencies:**
```bash
# Ubuntu/Debian
apt-get update
apt-get install -y php8.2-fpm php8.2-pgsql php8.2-redis php8.2-bcmath php8.2-gd php8.2-imagick nginx curl git composer nodejs npm
```

**Create Application User:**
```bash
useradd -m -s /bin/bash cleanflow
```

---

### 2. Application Deployment

**Clone Repository:**
```bash
cd /var/www
git clone https://github.com/your-org/cleanflow-app.git
cd cleanflow-app
chown -R cleanflow:cleanflow .
```

**Install Dependencies:**
```bash
sudo -u cleanflow composer install --no-dev --optimize-autoloader
sudo -u cleanflow npm install
sudo -u cleanflow npm run build
```

**Configure Environment:**
```bash
sudo -u cleanflow cp .env.example .env
# Edit .env with production values, including one stable APP_KEY generated
# once in a secure environment. Do not run key:generate on an existing app.
```

**Set Permissions:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R cleanflow:www-data storage bootstrap/cache
```

---

### 3. Database Setup

**Create Database & User:**
```bash
sudo -u postgres psql << EOF
CREATE DATABASE cleanflow_prod;
CREATE USER cleanflow_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE cleanflow_prod TO cleanflow_user;
ALTER DATABASE cleanflow_prod OWNER TO cleanflow_user;
EOF
```

**Run Migrations:**
```bash
sudo -u cleanflow php artisan migrate --force
```

**Production seed policy:**

Do not run `php artisan db:seed --force` in production. The default seeders
contain demo accounts and sample staff for local/staging testing. The staff
seeder refuses to run in the production environment, but production data
should still be created deliberately through reviewed migrations or the admin
workflow.

---

### 4. Web Server Configuration

**Nginx Configuration** (`/etc/nginx/sites-available/cleanflow`):

```nginx
server {
    listen 80;
    server_name cleanflow.example.com www.cleanflow.example.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name cleanflow.example.com www.cleanflow.example.com;

    ssl_certificate /etc/letsencrypt/live/cleanflow.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/cleanflow.example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root /var/www/cleanflow-app/public;
    index index.php;

    client_max_body_size 10M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(self \"https://*.daily.co\"), geolocation=(self), microphone=(self \"https://*.daily.co\")" always;
    add_header Strict-Transport-Security "max-age=31536000" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Enable Site:**
```bash
ln -s /etc/nginx/sites-available/cleanflow /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

---

### 5. SSL/TLS Certificate

**Using Let's Encrypt:**
```bash
apt-get install certbot python3-certbot-nginx
certbot certonly --nginx -d cleanflow.example.com -d www.cleanflow.example.com

# Auto-renewal
systemctl enable certbot.timer
systemctl start certbot.timer
```

---

### 6. Queue and Scheduler

**Create Systemd Service** (`/etc/systemd/system/cleanflow-queue.service`):

```ini
[Unit]
Description=Clean Flow Queue Worker
After=network.target

[Service]
Type=simple
User=cleanflow
WorkingDirectory=/var/www/cleanflow-app
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

**Enable Queue Worker:**
```bash
systemctl enable cleanflow-queue.service
systemctl start cleanflow-queue.service
```

**Cron Scheduler** (in cleanflow user's crontab):

```
* * * * * cd /var/www/cleanflow-app && php artisan schedule:run >> /dev/null 2>&1
```

---

### 7. Monitoring & Logging

**Configure Log Rotation** (`/etc/logrotate.d/cleanflow`):

```
/var/www/cleanflow-app/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 cleanflow www-data
    sharedscripts
}
```

**Monitor Application:**
```bash
# Check queue
php artisan queue:failed

# Clear failed queue jobs
php artisan queue:retry all

# Monitor logs
tail -f /var/www/cleanflow-app/storage/logs/laravel.log
```

---

### 8. Backup Strategy

**Daily Database Backup:**
```bash
#!/bin/bash
BACKUP_DIR="/backups/cleanflow"
mkdir -p $BACKUP_DIR
pg_dump -U cleanflow_user cleanflow_prod | gzip > $BACKUP_DIR/db-$(date +%Y%m%d-%H%M%S).sql.gz

# Keep only last 30 days
find $BACKUP_DIR -mtime +30 -delete
```

**Schedule in Crontab:**
```
0 2 * * * /usr/local/bin/backup-cleanflow.sh
```

---

## Post-Deployment

### Verify Installation

```bash
# Check app status
sudo -u cleanflow php artisan tinker
>>> DB::connection()->getPdo();  // Should return PDO instance
>>> \App\Models\User::count();   // Should return user count

# Test cache
php artisan tinker
>>> cache()->set('test', 'value', 60);
>>> cache()->get('test');  // Should return 'value'

# Test queue
php artisan tinker
>>> \App\Mail\BookingSubmitted::dispatch(...);
```

### Health Check Endpoint

Add to `routes/web.php` (or protect it):
```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'db' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::get('test_health_check') || true,
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

---

## Troubleshooting

### Application Won't Start
```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Regenerate optimized files
php artisan config:cache
php artisan route:cache
```

### Database Connection Issues
```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check database connectivity
pg_isready -h your-db-host -U cleanflow_user -d cleanflow_prod
```

### Queue Jobs Stuck
```bash
php artisan queue:failed      # View failed jobs
php artisan queue:retry all   # Retry all failed jobs
php artisan queue:flush       # Clear all jobs
```

### File Permission Issues
```bash
chown -R cleanflow:www-data /var/www/cleanflow-app
chmod -R 775 storage bootstrap/cache
```

---

## Security Hardening Checklist

- [ ] Enable HTTPS with valid SSL certificate
- [ ] Set strong database password
- [ ] Disable debug mode in production
- [ ] Configure firewall rules (ufw/iptables)
- [ ] Regular security updates: `composer audit`
- [ ] Implement WAF (ModSecurity/Cloudflare)
- [ ] Rate limiting enabled on API endpoints
- [ ] CORS headers properly configured
- [ ] CSRF protection tokens enabled
- [ ] Session security: secure & httponly flags
- [ ] Database backups encrypted and stored offsite
- [ ] Monitor error logs for attacks
- [ ] Keep dependencies updated monthly

---

## Rollback Procedure

If deployment fails:

```bash
# Check recent commits
git log --oneline -10

# Rollback to previous version
git revert <commit-hash>
git push

# Or revert to specific tag
git checkout tags/v1.0.0
git push -f

# Restart services
systemctl restart php8.2-fpm nginx cleanflow-queue.service
```

---

## Support

For deployment issues:
- Check logs: `/var/www/cleanflow-app/storage/logs/`
- Review Nginx error logs: `/var/log/nginx/error.log`
- Check PHP-FPM status: `systemctl status php8.2-fpm`
- Monitor queue: `php artisan queue:work -v`

