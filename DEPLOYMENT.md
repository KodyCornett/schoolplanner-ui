# Deployment Guide: Modulus to AWS EC2 (Free Tier)

---

## Production Checklist

Before going live, ensure all items in this checklist are completed:

### Required Environment Settings

| Setting | Development | Production | Notes |
|---------|-------------|------------|-------|
| `APP_ENV` | `local` | `production` | Enables production optimizations |
| `APP_DEBUG` | `true` | `false` | **Critical**: Prevents error details from leaking |
| `LOG_LEVEL` | `debug` | `warning` | Reduces log noise, improves performance |
| `SESSION_SECURE_COOKIE` | `false` | `true` | Required when using HTTPS |
| `MAIL_MAILER` | `log` | `smtp`/`ses`/etc. | Emails won't send with `log` mailer |

### Pre-Deployment Tasks

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set `LOG_LEVEL=warning` in `.env`
- [ ] Set `SESSION_SECURE_COOKIE=true` (if using HTTPS)
- [ ] Configure email provider (not `log` mailer)
- [ ] Set `MAIL_FROM_ADDRESS` to a real address
- [ ] Configure Stripe production keys
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Verify SSL certificate is valid
- [ ] Test email delivery (password reset, verification)
- [ ] Test Stripe webhooks

### Security Verification

- [ ] Confirm `APP_DEBUG=false` (visit `/up` to verify no stack traces)
- [ ] Confirm security headers are present (check browser DevTools → Network → Response Headers)
- [ ] Confirm rate limiting works (rapidly hit API endpoints, expect 429 response)
- [ ] Confirm email verification is enforced (new users cannot access `/plan/import` without verifying)

### Post-Deployment

- [ ] Monitor `storage/logs/laravel.log` for errors
- [ ] Set up log rotation or external logging service
- [ ] Configure server firewall (only ports 22, 80, 443)
- [ ] Schedule regular backups of `database/database.sqlite`

---

This guide walks you through deploying the Modulus application to an AWS EC2 instance from scratch using the Free Tier.

**Setup:** Manual EC2 configuration with SQLite database, accessed via IP address.

**Estimated cost:** Free for 12 months (within Free Tier limits), then ~$8-10/month

---

## AWS Free Tier Overview

| Resource | Free Tier Limit | Notes |
|----------|-----------------|-------|
| EC2 Instance | t2.micro (1 vCPU, 1GB RAM) | 750 hours/month for 12 months |
| EBS Storage | 30GB | General Purpose SSD (gp2) |
| Data Transfer | 15GB/month outbound | Inbound is free |
| Elastic IP | 1 (when attached) | Free only when attached to running instance |

**Important:** The 1GB RAM is tight for this stack. This guide includes memory optimization steps that are critical for stable operation.

---

## Phase 1: Create AWS Account

1. Go to https://aws.amazon.com
2. Click **"Create an AWS Account"**
3. Enter email address and choose an account name
4. Verify your email address
5. Add payment information (credit card required, won't be charged within Free Tier)
6. Choose the **"Basic support - Free"** plan
7. Sign in to the AWS Management Console

---

## Phase 2: Launch EC2 Instance

### 2.1 Navigate to EC2

1. In the AWS Console search bar, type **"EC2"** and click on it
2. Ensure you're in **us-east-1 (N. Virginia)** region (top-right dropdown) - this region has the best Free Tier availability

### 2.2 Create Key Pair (for SSH access)

1. In the left sidebar, click **"Key Pairs"** under "Network & Security"
2. Click **"Create key pair"**
3. Configure:
   - Name: `modulus-key`
   - Key pair type: **RSA**
   - Private key file format: **.pem** (for OpenSSH) or **.ppk** (for PuTTY on Windows)
4. Click **"Create key pair"**
5. **Save the downloaded file securely** - you cannot download it again

### 2.3 Create Security Group

1. In the left sidebar, click **"Security Groups"** under "Network & Security"
2. Click **"Create security group"**
3. Configure:
   - Security group name: `modulus-sg`
   - Description: `Security group for Modulus application`
   - VPC: Leave default
4. Add **Inbound rules**:

| Type | Port | Source | Description |
|------|------|--------|-------------|
| SSH | 22 | My IP | SSH access |
| HTTP | 80 | Anywhere (0.0.0.0/0) | Web traffic |
| HTTPS | 443 | Anywhere (0.0.0.0/0) | Future SSL |

5. Click **"Create security group"**

### 2.4 Launch Instance

1. In the left sidebar, click **"Instances"**
2. Click **"Launch instances"**
3. Configure:

| Setting | Value |
|---------|-------|
| **Name** | `modulus` |
| **AMI** | Ubuntu Server 24.04 LTS (Free tier eligible) |
| **Instance type** | t2.micro (Free tier eligible) |
| **Key pair** | `modulus-key` (the one you created) |
| **Security group** | Select existing → `modulus-sg` |
| **Storage** | 20 GB gp2 (within 30GB free tier limit) |

4. Click **"Launch instance"**
5. Wait ~60 seconds for it to initialize
6. Click on the instance ID to view details
7. **Copy the Public IPv4 address** (e.g., `54.123.45.67`)

### 2.5 Allocate Elastic IP (Recommended)

An Elastic IP gives you a static IP that persists across instance stops/starts.

1. In the left sidebar, click **"Elastic IPs"** under "Network & Security"
2. Click **"Allocate Elastic IP address"**
3. Click **"Allocate"**
4. Select the new Elastic IP, click **"Actions"** → **"Associate Elastic IP address"**
5. Select your `modulus` instance and click **"Associate"**
6. **Use this Elastic IP for all future connections**

**Note:** Elastic IPs are free only when attached to a running instance. You'll be charged ~$0.005/hour if the instance is stopped but the IP remains allocated.

---

## Phase 3: Connect to Your Server

### On Windows (PowerShell)

1. Open **PowerShell**
2. Navigate to where you saved your key:
```powershell
cd C:\Users\YourName\Downloads
```
3. Set correct permissions on the key file:
```powershell
icacls modulus-key.pem /inheritance:r /grant:r "$($env:USERNAME):(R)"
```
4. Connect via SSH:
```powershell
ssh -i modulus-key.pem ubuntu@YOUR_ELASTIC_IP
```
5. Type `yes` when asked about the fingerprint
6. You're now on your server!

### On Windows (PuTTY Alternative)

1. Download PuTTY from https://www.putty.org
2. If you downloaded the `.pem` file, convert it using PuTTYgen:
   - Open PuTTYgen → Load → Select your `.pem` file
   - Click "Save private key" as `.ppk`
3. Open PuTTY:
   - Host Name: `ubuntu@YOUR_ELASTIC_IP`
   - Connection → SSH → Auth → Credentials: Browse to your `.ppk` file
   - Click "Open"

---

## Phase 4: Memory Optimization (Critical for 1GB RAM)

**Run these steps first** - the 1GB RAM on t2.micro is tight for PHP-FPM + Java + queue workers.

### 4.1 Create Swap File

```bash
sudo fallocate -l 1G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

Verify swap is active:
```bash
free -h
```
You should see 1GB of swap space.

### 4.2 Configure Swappiness

Reduce swappiness so the system prefers RAM over swap (better performance):
```bash
echo 'vm.swappiness=10' | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

---

## Phase 5: Install Server Dependencies

Run these commands one section at a time:

### 5.1 Update System
```bash
sudo apt update && sudo apt upgrade -y
```

### 5.2 Install Nginx (Web Server)
```bash
sudo apt install nginx -y
sudo systemctl enable nginx
```

### 5.3 Install PHP 8.3
```bash
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.3-fpm php8.3-cli php8.3-common php8.3-xml php8.3-curl php8.3-mbstring php8.3-zip php8.3-bcmath php8.3-sqlite3 -y
```

### 5.4 Configure PHP-FPM Memory Limits

Edit PHP-FPM pool configuration to limit memory usage:
```bash
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

Find and modify these settings:
```ini
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
```

Save: `Ctrl+X`, `Y`, `Enter`

Edit PHP memory limit:
```bash
sudo nano /etc/php/8.3/fpm/php.ini
```

Find and set:
```ini
memory_limit = 128M
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.3-fpm
```

### 5.5 Install Composer (PHP Package Manager)
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### 5.6 Install Node.js 20
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs -y
```

### 5.7 Install Java 17 (Required for Kotlin Engine)
```bash
sudo apt install openjdk-17-jre-headless -y
```

Note: Using `headless` variant saves memory by excluding GUI libraries.

### 5.8 Install Supervisor (Manages Background Processes)
```bash
sudo apt install supervisor -y
sudo systemctl enable supervisor
```

### 5.9 Install Git
```bash
sudo apt install git -y
```

### 5.10 Verify Installations
```bash
php -v          # Should show PHP 8.3.x
composer -V     # Should show Composer version
node -v         # Should show v20.x.x
java -version   # Should show openjdk 17.x.x
nginx -v        # Should show nginx version
free -h         # Verify swap is active
```

---

## Phase 6: Create Application User

Create a dedicated user for running the application:

```bash
sudo adduser --disabled-password --gecos "" deploy
sudo usermod -aG www-data deploy
```

---

## Phase 7: Deploy the Application

### 7.1 Switch to Deploy User
```bash
sudo su - deploy
```

### 7.2 Clone Your Repository

**Option A: If your repo is public**
```bash
git clone https://github.com/YOUR_USERNAME/modulus-ui.git
cd modulus-ui
```

**Option B: If your repo is private**

First, generate a GitHub personal access token:
1. Go to GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token with `repo` scope
3. Copy the token

Then clone:
```bash
git clone https://YOUR_TOKEN@github.com/YOUR_USERNAME/modulus-ui.git
cd modulus-ui
```

### 7.3 Install PHP Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 7.4 Install Node Dependencies and Build Frontend
```bash
npm ci
npm run build
```

### 7.5 Configure Environment

Create the environment file:
```bash
cp .env.example .env
nano .env
```

**Edit these values in the `.env` file:**

```env
APP_NAME="Modulus"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://YOUR_ELASTIC_IP

DB_CONNECTION=sqlite
# Comment out or remove DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

JAVA_BIN=/usr/bin/java
SCHOOLPLAN_JAR_PATH=/home/deploy/modulus-ui/storage/app/engine/SchoolCalendarSync1-all.jar
```

Save: Press `Ctrl+X`, then `Y`, then `Enter`

### 7.6 Create SQLite Database
```bash
touch database/database.sqlite
```

### 7.7 Generate App Key and Run Migrations
```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
```

### 7.8 Set Permissions
```bash
chmod -R 775 storage bootstrap/cache database
```

### 7.9 Create Engine Directory
```bash
mkdir -p storage/app/engine
mkdir -p storage/app/private
```

### 7.10 Exit Back to Ubuntu User
```bash
exit
```

---

## Phase 8: Upload the Kotlin JAR

From your **local Windows machine** (not the server), upload the JAR file.

### Using PowerShell with SCP
```powershell
scp -i C:\Users\YourName\Downloads\modulus-key.pem "C:\path\to\SchoolCalendarSync1-all.jar" ubuntu@YOUR_ELASTIC_IP:/tmp/
```

Then on the server, move it to the correct location:
```bash
sudo mv /tmp/SchoolCalendarSync1-all.jar /home/deploy/modulus-ui/storage/app/engine/
sudo chown deploy:deploy /home/deploy/modulus-ui/storage/app/engine/SchoolCalendarSync1-all.jar
```

### Alternative: Using WinSCP (GUI Tool)

1. Download WinSCP from https://winscp.net
2. Connect with:
   - Host: YOUR_ELASTIC_IP
   - Username: `ubuntu`
   - Use your `.ppk` private key for authentication
3. Navigate to `/home/deploy/modulus-ui/storage/app/engine/`
4. Drag and drop the JAR file
5. Run the `chown` command above to fix ownership

---

## Phase 9: Configure Nginx

### 9.1 Create Nginx Configuration
```bash
sudo nano /etc/nginx/sites-available/modulus
```

Paste this configuration:
```nginx
server {
    listen 80;
    server_name _;
    root /home/deploy/modulus-ui/public;

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

### 9.2 Enable the Site
```bash
sudo ln -s /etc/nginx/sites-available/modulus /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## Phase 10: Configure Background Services

The app needs two background processes:
1. **Queue worker** - processes background jobs
2. **File server** - serves ICS files to the Kotlin engine on port 8001

### 10.1 Create Queue Worker Config
```bash
sudo nano /etc/supervisor/conf.d/modulus-worker.conf
```

Paste:
```ini
[program:modulus-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/deploy/modulus-ui/artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
user=deploy
numprocs=1
redirect_stderr=true
stdout_logfile=/home/deploy/modulus-ui/storage/logs/worker.log
stopwaitsecs=3600
```

Save: `Ctrl+X`, `Y`, `Enter`

### 10.2 Create File Server Config
```bash
sudo nano /etc/supervisor/conf.d/modulus-fileserver.conf
```

Paste:
```ini
[program:modulus-fileserver]
command=php -S 127.0.0.1:8001 router.php
directory=/home/deploy/modulus-ui/storage/app/private
autostart=true
autorestart=true
user=deploy
redirect_stderr=true
stdout_logfile=/home/deploy/modulus-ui/storage/logs/fileserver.log
```

Save: `Ctrl+X`, `Y`, `Enter`

### 10.3 Set Java Heap Size for Memory Optimization

Create an environment file for the Java process:
```bash
sudo nano /etc/supervisor/conf.d/modulus-java-env.conf
```

This isn't a separate process - instead, update your `.env` file to include Java options:
```bash
sudo su - deploy
nano /home/deploy/modulus-ui/.env
```

Add this line:
```env
JAVA_OPTS=-Xmx256m -Xms128m
```

Exit back to ubuntu user:
```bash
exit
```

Note: The application code should read `JAVA_OPTS` when invoking the JAR. If it doesn't, the heap defaults will be used, which should be fine with swap enabled.

### 10.4 Start the Services
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

### 10.5 Verify Services Are Running
```bash
sudo supervisorctl status
```

Should show both services as `RUNNING`.

---

## Phase 11: Test Your Deployment

### 11.1 Visit Your Application

Open a browser and go to: `http://YOUR_ELASTIC_IP`

You should see the Modulus homepage.

### 11.2 Test the Full Flow

1. Navigate to `/plan/import`
2. Upload a Canvas ICS file
3. Configure settings and submit
4. Verify the preview page loads with the calendar
5. Test the download functionality

### 11.3 Monitor Memory Usage

Keep an eye on memory during heavy operations:
```bash
watch -n 2 free -h
```

If you see swap being heavily used during normal operation, the instance may struggle under load.

---

## Troubleshooting

### Check Laravel Logs
```bash
tail -f /home/deploy/modulus-ui/storage/logs/laravel.log
```

### Check Nginx Logs
```bash
sudo tail -f /var/log/nginx/error.log
```

### Check Supervisor/Worker Logs
```bash
tail -f /home/deploy/modulus-ui/storage/logs/worker.log
tail -f /home/deploy/modulus-ui/storage/logs/fileserver.log
```

### Restart Services
```bash
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
sudo supervisorctl restart all
```

### Test Java Manually
```bash
sudo su - deploy
java -Xmx256m -jar /home/deploy/modulus-ui/storage/app/engine/SchoolCalendarSync1-all.jar --help
```

### Permission Issues
```bash
sudo chown -R deploy:www-data /home/deploy/modulus-ui/storage
sudo chmod -R 775 /home/deploy/modulus-ui/storage
```

### Out of Memory Issues

If the instance becomes unresponsive:
1. In AWS Console, select the instance
2. Click **Instance state** → **Stop instance**
3. Wait for it to stop, then **Start instance**
4. Consider increasing swap size:
```bash
sudo swapoff /swapfile
sudo fallocate -l 2G /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
```

### Security Group Issues

If you can't connect:
1. Go to EC2 → Security Groups → `modulus-sg`
2. Check that SSH (port 22) allows your current IP
3. Your IP may have changed - update the rule with "My IP"

---

## Updating the Application (Future Deployments)

When you make changes and want to deploy:

```bash
# SSH into server
ssh -i modulus-key.pem ubuntu@YOUR_ELASTIC_IP

# Switch to deploy user
sudo su - deploy
cd modulus-ui

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
exit  # back to ubuntu
sudo supervisorctl restart modulus-worker
```

---

## Cost Summary

### During Free Tier (First 12 Months)

| Item | Cost |
|------|------|
| EC2 t2.micro (750 hrs/month) | Free |
| EBS Storage (20GB of 30GB free) | Free |
| Data Transfer (15GB/month) | Free |
| Elastic IP (attached to running instance) | Free |
| **Total** | **$0/month** |

### After Free Tier Expires

| Item | Cost |
|------|------|
| EC2 t2.micro (on-demand, us-east-1) | ~$8.50/month |
| EBS Storage (20GB gp2) | ~$2/month |
| Data Transfer | ~$0.50/month (varies by usage) |
| Elastic IP | Free (when attached) |
| **Total** | **~$11/month** |

**Cost-Saving Tips:**
- Use Reserved Instances for ~40% savings if committing to 1 year
- Consider Spot Instances for non-critical workloads
- Stop the instance when not in use (but keep Elastic IP attached or release it)

---

## Security Reminders

- Keep `APP_DEBUG=false` in production (never set to true)
- Regularly update server: `sudo apt update && sudo apt upgrade`
- Keep your `.pem` key file secure and never share it
- Restrict SSH access to your IP only in the security group
- Back up your SQLite database periodically: `/home/deploy/modulus-ui/database/database.sqlite`
- Consider enabling AWS CloudWatch for monitoring

---

## Adding a Domain and SSL (Optional Future Step)

If you later purchase a domain:

### Point Domain to Your Instance

1. In your domain registrar (e.g., Namecheap, GoDaddy), add an **A record**:
   - Host: `@` (or leave blank)
   - Value: Your Elastic IP
   - TTL: 300 (or default)

2. Optionally add a `www` subdomain:
   - Host: `www`
   - Value: Your Elastic IP

### Update Application

```bash
sudo su - deploy
nano /home/deploy/modulus-ui/.env
```

Update:
```env
APP_URL=https://your-domain.com
```

### Install SSL Certificate

```bash
exit  # back to ubuntu user
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

Follow the prompts to complete SSL setup. Certbot will automatically configure Nginx for HTTPS.

### Auto-Renewal

Certbot sets up auto-renewal automatically. Verify with:
```bash
sudo certbot renew --dry-run
```
