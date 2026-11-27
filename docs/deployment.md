# Deployment

## Overview

Point requires PHP 8.4.27 or later. Use a server and PHP SAPI configured to route application requests to the front controller.

When deploying behind a reverse proxy, configure the trusted edge to validate the host and strip or overwrite forwarding headers from clients. Point has no built-in trusted-proxy configuration. If the application uses forwarded scheme, host, client address or path information, interpret it only after validating the proxy connection. Configure the application base path explicitly when serving under a URL prefix.

## PHP Built-in Server (Development Only)

```bash
# From project root
php -S localhost:8080 -t public
```

**Note**: Not suitable for production. Single-threaded, no concurrency.

## Apache

### Virtual Host Configuration

```apache
<VirtualHost *:80>
    ServerName your-app.example.com
    DocumentRoot /var/www/your-app/public
    
    <Directory /var/www/your-app/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # PHP-FPM (recommended)
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.4-fpm.sock|fcgi://localhost"
    </FilesMatch>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/your-app-error.log
    CustomLog ${APACHE_LOG_DIR}/your-app-access.log combined
</VirtualHost>
```

### .htaccess (public/.htaccess)

```apache
RewriteEngine On

# Prevent access to sensitive files
RewriteRule ^\.env - [F,L]
RewriteRule ^composer\. - [F,L]
RewriteRule ^\.git - [F,L]

# Front controller
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# HTTPS enforcement (uncomment for production)
# RewriteCond %{HTTPS} off
# RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Enable Required Modules

```bash
a2enmod rewrite headers proxy proxy_fcgi setenvif
systemctl reload apache2
```

## Nginx

### Virtual Host Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-app.example.com;
    root /var/www/your-app/public;
    index index.php;
    
    # Security
    location ~ /\.(env|git|composer) {
        deny all;
        return 404;
    }
    
    # Front controller
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        include fastcgi_params;
        fastcgi_intercept_errors on;
    }
    
    # Static assets caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Logging
    access_log /var/log/nginx/your-app-access.log;
    error_log /var/log/nginx/your-app-error.log;
}
```

### PHP-FPM Pool (www.conf)

```ini
[your-app]
user = www-data
group = www-data
listen = /run/php/php8.4-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Security
security.limit_extensions = .php
php_admin_value[open_basedir] = /var/www/your-app:/tmp
php_admin_flag[expose_php] = off
```

## Base Path (Subdirectory Deployment)

For applications in a subdirectory (e.g., `example.com/myapp`):

### Apache

```apache
Alias /myapp /var/www/your-app/public

<Directory /var/www/your-app/public>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### Nginx

```nginx
location /myapp {
    alias /var/www/your-app/public;
    try_files $uri $uri/ /myapp/index.php?$query_string;
    
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /var/www/your-app/public/index.php;
        include fastcgi_params;
    }
}
```

### Point Configuration

```php
$app->setBasePath('/myapp');
```

This ensures:
- Routes match correctly (`/myapp/users` → `GET /users`)
- URL generation includes base path
- Asset paths work correctly

## Environment Configuration

### .env File (Not Committed)

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.example.com

# Database
DB_HOST=localhost
DB_NAME=your_app
DB_USER=your_app
DB_PASS=secure_password

# Logging
LOG_CHANNEL=stderr
LOG_LEVEL=error

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Loading .env (Optional)

```php
// In public/index.php, before creating app
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Or use symfony/dotenv
use Symfony\Component\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');
```

## Production Checklist

- [ ] `APP_DEBUG=false` / `displayErrorDetails=false`
- [ ] OPcache enabled in php.ini
- [ ] Composer optimized autoloader: `composer dump-autoload -o --classmap-authoritative`
- [ ] Remove development dependencies: `composer install --no-dev`
- [ ] Configure proper logging (Monolog to file/syslog)
- [ ] Set up monitoring (health check endpoint)
- [ ] Configure HTTPS (SSL/TLS certificates)
- [ ] Set security headers (CSP, HSTS, etc.)
- [ ] Configure backup strategy
- [ ] Set up deployment pipeline

## OPcache Configuration (php.ini)

```ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

**Note**: Set `opcache.validate_timestamps=0` and `opcache.revalidate_freq=0` for production. Clear OPcache on deploy.

## Health Check Endpoint

```php
$app->get('/health', function ($request, $response) {
    // Check database, cache, external services
    $checks = [
        'database' => $this->checkDatabase(),
        'cache' => $this->checkCache(),
    ];
    
    $status = array_reduce($checks, fn($c, $v) => $c && $v, true) ? 200 : 503;
    
    return $response
        ->withStatus($status)
        ->withHeader('Content-Type', 'application/json')
        ->withJson(['status' => $status === 200 ? 'healthy' : 'unhealthy', 'checks' => $checks]);
});
```

## Docker Deployment

### Dockerfile

```dockerfile
FROM php:8.4-fpm-alpine

# Install extensions
RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install \
    intl \
    zip \
    mbstring \
    opcache \
    pdo_mysql

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (cache layer)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader

# Copy application
COPY . .

# Generate autoloader
RUN composer dump-autoload -o --classmap-authoritative

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

USER www-data
EXPOSE 9000
CMD ["php-fpm"]
```

### docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build: .
    volumes:
      - .:/var/www/html
    environment:
      - APP_ENV=production
    depends_on:
      - db
      - redis

  webserver:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: your_app
      MYSQL_USER: your_app
      MYSQL_PASSWORD: secure_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - db_data:/var/lib/mysql

  redis:
    image: redis:alpine

volumes:
  db_data:
```

---

## Related Documentation

- [Application](application.md)
- [Installation](installation.md)
- [Testing](testing.md)