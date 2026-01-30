# Deployment Guide: School Planner UI to DigitalOcean Droplet

This guide walks you through deploying the School Planner application to a DigitalOcean Droplet from scratch.

**Setup:** Manual Droplet configuration with SQLite database, accessed via IP address.

**Estimated cost:** ~$12/month (2GB Droplet)

---

## Phase 1: Create DigitalOcean Account

1. Go to https://www.digitalocean.com
2. Click **"Sign Up"**
3. Create account with email, GitHub, or Google
4. Verify your email address
5. Add a payment method (credit card or PayPal)
6. New users often get **$200 free credits for 60 days**

---

## Phase 2: Create a Droplet

1. Log into DigitalOcean dashboard
2. Click the green **"Create"** button (top right)
3. Select **"Droplets"**

### Configuration

| Setting | Value |
|---------|-------|
| **Region** | Choose closest to you (e.g., New York, San Francisco, London) |
| **Image** | Ubuntu 24.04 (LTS) x64 |
| **Size** | Basic → Regular → **$12/mo (2GB RAM, 1 CPU)** |
| **Authentication** | **Password** (easier for beginners) - create a strong root password |
| **Hostname** | `schoolplanner` (or any name you like) |

4. Click **"Create Droplet"**
5. Wait ~60 seconds for it to spin up
6. **Copy the IP address** shown (e.g., `164.92.123.45`) - you'll need this

---

## Phase 3: Connect to Your Server

### On Windows

1. Open **PowerShell** or **Command Prompt**
2. Connect via SSH:
```bash
ssh root@YOUR_DROPLET_IP
```
3. Type `yes` when asked about fingerprint
4. Enter your root password
5. You're now on your server!

---

## Phase 4: Install Server Dependencies

Run these commands one section at a time on your Droplet:

### 4.1 Update System
```bash
apt update && apt upgrade -y
```

### 4.2 Install Nginx (Web Server)
```bash
apt install nginx -y
systemctl enable nginx
```

### 4.3 Install PHP 8.3
```bash
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.3-fpm php8.3-cli php8.3-common php8.3-xml php8.3-curl php8.3-mbstring php8.3-zip php8.3-bcmath php8.3-sqlite3 -y
```

### 4.4 Install Composer (PHP Package Manager)
```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

### 4.5 Install Node.js 20
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install nodejs -y
```

### 4.6 Install Java 17 (Required for Kotlin Engine)
```bash
apt install openjdk-17-jre -y
```

### 4.7 Install Supervisor (Manages Background Processes)
```bash
apt install supervisor -y
systemctl enable supervisor
```

### 4.8 Install Git
```bash
apt install git -y
```

### Verify Installations
```bash
php -v          # Should show PHP 8.3.x
composer -V     # Should show Composer version
node -v         # Should show v20.x.x
java -version   # Should show openjdk 17.x.x
nginx -v        # Should show nginx version
```

---

## Phase 5: Create Application User

Create a dedicated user for running the application (more secure than using root):

```bash
adduser --disabled-password --gecos "" deploy
usermod -aG www-data deploy
```

---

## Phase 6: Deploy the Application

### 6.1 Switch to Deploy User
```bash
su - deploy
```

### 6.2 Clone Your Repository

**Option A: If your repo is public**
```bash
git clone https://github.com/YOUR_USERNAME/schoolplanner-ui.git
cd schoolplanner-ui
```

**Option B: If your repo is private**

First, generate a GitHub personal access token:
1. Go to GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token with `repo` scope
3. Copy the token

Then clone:
```bash
git clone https://YOUR_TOKEN@github.com/YOUR_USERNAME/schoolplanner-ui.git
cd schoolplanner-ui
```

### 6.3 Install PHP Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 6.4 Install Node Dependencies and Build Frontend
```bash
npm ci
npm run build
```

### 6.5 Configure Environment

Create the environment file:
```bash
cp .env.example .env
nano .env
```

**Edit these values in the `.env` file:**

```env
APP_NAME="School Planner"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://YOUR_DROPLET_IP

DB_CONNECTION=sqlite
# Comment out or remove DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

JAVA_BIN=/usr/bin/java
SCHOOLPLAN_JAR_PATH=/home/deploy/schoolplanner-ui/storage/app/engine/SchoolCalendarSync1-all.jar
```

Save: Press `Ctrl+X`, then `Y`, then `Enter`

### 6.6 Create SQLite Database
```bash
touch database/database.sqlite
```

### 6.7 Generate App Key and Run Migrations
```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
```

### 6.8 Set Permissions
```bash
chmod -R 775 storage bootstrap/cache database
```

### 6.9 Create Engine Directory
```bash
mkdir -p storage/app/engine
mkdir -p storage/app/private
```

### 6.10 Exit Back to Root
```bash
exit
```

---

## Phase 7: Upload the Kotlin JAR

From your **local Windows machine** (not the server), upload the JAR file.

### Using PowerShell/Command Prompt with SCP
```bash
scp "C:\path\to\SchoolCalendarSync1-all.jar" root@YOUR_DROPLET_IP:/home/deploy/schoolplanner-ui/storage/app/engine/
```

### Alternative: Using WinSCP (GUI Tool)
1. Download WinSCP from https://winscp.net
2. Connect with:
   - Host: YOUR_DROPLET_IP
   - Username: root
   - Password: your root password
3. Navigate to `/home/deploy/schoolplanner-ui/storage/app/engine/`
4. Drag and drop the JAR file

### Fix Ownership After Upload
Back on the server:
```bash
chown deploy:deploy /home/deploy/schoolplanner-ui/storage/app/engine/SchoolCalendarSync1-all.jar
```

---

## Phase 8: Configure Nginx

### 8.1 Create Nginx Configuration
```bash
nano /etc/nginx/sites-available/schoolplanner
```

Paste this configuration:
```nginx
server {
    listen 80;
    server_name _;
    root /home/deploy/schoolplanner-ui/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Save: `Ctrl+X`, `Y`, `Enter`

### 8.2 Enable the Site
```bash
ln -s /etc/nginx/sites-available/schoolplanner /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
```

---

## Phase 9: Configure Background Services

The app needs two background processes:
1. **Queue worker** - processes background jobs
2. **File server** - serves ICS files to the Kotlin engine on port 8001

### 9.1 Create Queue Worker Config
```bash
nano /etc/supervisor/conf.d/schoolplanner-worker.conf
```

Paste:
```ini
[program:schoolplanner-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/deploy/schoolplanner-ui/artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
user=deploy
numprocs=1
redirect_stderr=true
stdout_logfile=/home/deploy/schoolplanner-ui/storage/logs/worker.log
stopwaitsecs=3600
```

Save: `Ctrl+X`, `Y`, `Enter`

### 9.2 Create File Server Config
```bash
nano /etc/supervisor/conf.d/schoolplanner-fileserver.conf
```

Paste:
```ini
[program:schoolplanner-fileserver]
command=php -S 127.0.0.1:8001 router.php
directory=/home/deploy/schoolplanner-ui/storage/app/private
autostart=true
autorestart=true
user=deploy
redirect_stderr=true
stdout_logfile=/home/deploy/schoolplanner-ui/storage/logs/fileserver.log
```

Save: `Ctrl+X`, `Y`, `Enter`

### 9.3 Start the Services
```bash
supervisorctl reread
supervisorctl update
supervisorctl start all
```

### 9.4 Verify Services Are Running
```bash
supervisorctl status
```

Should show both services as `RUNNING`.

---

## Phase 10: Configure Firewall

```bash
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS (for future)
ufw enable
ufw status
```

Type `y` when prompted.

---

## Phase 11: Test Your Deployment

### 11.1 Visit Your Application
Open a browser and go to: `http://YOUR_DROPLET_IP`

You should see the School Planner homepage.

### 11.2 Test the Full Flow
1. Navigate to `/plan/import`
2. Upload a Canvas ICS file
3. Configure settings and submit
4. Verify the preview page loads with the calendar
5. Test the download functionality

---

## Troubleshooting

### Check Laravel Logs
```bash
tail -f /home/deploy/schoolplanner-ui/storage/logs/laravel.log
```

### Check Nginx Logs
```bash
tail -f /var/log/nginx/error.log
```

### Check Supervisor/Worker Logs
```bash
tail -f /home/deploy/schoolplanner-ui/storage/logs/worker.log
tail -f /home/deploy/schoolplanner-ui/storage/logs/fileserver.log
```

### Restart Services
```bash
systemctl restart nginx
systemctl restart php8.3-fpm
supervisorctl restart all
```

### Test Java Manually
```bash
su - deploy
java -jar /home/deploy/schoolplanner-ui/storage/app/engine/SchoolCalendarSync1-all.jar --help
```

### Permission Issues
```bash
chown -R deploy:www-data /home/deploy/schoolplanner-ui/storage
chmod -R 775 /home/deploy/schoolplanner-ui/storage
```

---

## Updating the Application (Future Deployments)

When you make changes and want to deploy:

```bash
# SSH into server
ssh root@YOUR_DROPLET_IP

# Switch to deploy user
su - deploy
cd schoolplanner-ui

# Pull latest code
git pull origin main

# Install dependencies if changed
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Run migrations if needed
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue worker
exit  # back to root
supervisorctl restart schoolplanner-worker
```

---

## Cost Summary

| Item | Cost |
|------|------|
| DigitalOcean Droplet (2GB RAM) | $12/month |
| Domain (optional, for later) | ~$12/year |
| **Total** | **$12/month** |

---

## Security Reminders

- Keep `APP_DEBUG=false` in production (never set to true)
- Regularly update server: `apt update && apt upgrade`
- Never share your root password
- Consider setting up SSH keys instead of password auth (more secure)
- Back up your SQLite database periodically: `/home/deploy/schoolplanner-ui/database/database.sqlite`

---

## Adding a Domain (Optional Future Step)

If you later purchase a domain:

1. In your domain registrar, add an **A record** pointing to your Droplet IP
2. Update `.env` on the server: `APP_URL=https://your-domain.com`
3. Install SSL certificate:
```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d your-domain.com
```