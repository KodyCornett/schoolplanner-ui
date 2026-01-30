FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    openjdk-17-jre-headless \
    git \
    unzip \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    libzip-dev \
    nginx \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js for Vite build
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy package files for npm
COPY package.json package-lock.json ./
RUN npm ci

# Copy application code
COPY . .

# Complete composer install with scripts
RUN composer dump-autoload --optimize

# Build frontend assets
RUN npm run build

# Copy Kotlin JAR to a fixed location
RUN mkdir -p /opt/schoolplan && \
    if [ -f storage/app/engine/SchoolCalendarSync1-all.jar ]; then \
        cp storage/app/engine/SchoolCalendarSync1-all.jar /opt/schoolplan/; \
    fi

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Configure nginx
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Configure supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Environment variables
ENV SCHOOLPLAN_JAR_PATH=/opt/schoolplan/SchoolCalendarSync1-all.jar
ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 8080

# Start supervisor (manages nginx + php-fpm)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
