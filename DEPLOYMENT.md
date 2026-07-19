# GSM Guard - Enterprise Production Deployment & Hardening Guide

This document defines the operational standards, configuration parameters, and optimization guidelines required to run GSM Guard in a production-hardened environment.

---

## 1. Prerequisites & System Requirements

| Parameter | Required Specification | Recommended Specification |
| :--- | :--- | :--- |
| **Interpreter** | PHP 8.0+ | PHP 8.2+ |
| **Database** | MariaDB 10.4+ / MySQL 5.7+ | MariaDB 10.11+ / MySQL 8.0+ |
| **Web Server** | Apache 2.4+ (with `mod_rewrite`) | Apache 2.4+ or Nginx 1.22+ |
| **SSL/TLS** | TLS 1.2+ Required | TLS 1.3 |

### Required PHP Extensions
Ensure the following extensions are active in your `php.ini`:
- `pdo` and `pdo_mysql` (Database access)
- `openssl` (AES encryption and HMAC signatures)
- `session` (Active session management)
- `mbstring` (Multi-byte string conversions)

---

## 2. Interactive Browser Installation Wizard

GSM Guard features an interactive Setup Wizard that runs environment diagnostics, creates configurations, and imports database schemas automatically.

### Steps to Run the Installer:
1. Ensure the web server has write access to the root directory and the `logs/` folder.
2. Point your browser to `https://your-domain.com/install.php` (or local path `http://localhost/gsm-security/public/install.php`).
3. **Step 1: Diagnostics**: Review validation checks. If any dependency reports "FAIL" or "LOCKED", resolve permissions or enable PHP modules and reload.
4. **Step 2: Parameters**: Fill in the Database Host, Port, Username, Password, Database Name, Application URL, and toggle the Environment to **Production**.
5. **Step 3: Setup Finalized**: The wizard tests the connection, imports `database/schema.sql` queries, generates a random 32-byte secret key, writes `.env`, and creates a locking flag file `logs/install.lock`.

> [!WARNING]
> The installation wizard automatically locks itself upon completion. To re-run the wizard, you must manually delete the `logs/install.lock` file.

---

## 3. Production Configuration (.env)

The `.env` configuration file contains environment-specific settings. Ensure these parameters are set for production:

```ini
# Core state
APP_ENV=production
APP_URL=https://your-domain.com

# Database settings
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=gsm_prod_db_user
DB_PASS=SecurePasswordXYZ_999##
DB_NAME=gsm_security

# Security Master Key (Keep private, never commit to git)
# Generated automatically by installer
APP_SECRET_KEY=38b7264a2f8194a01c402be96d66e74f_##@@

# Cookie settings
SESSION_LIFETIME=900
SESSION_SECURE=true
```

---

## 4. Security Hardening Guidelines

### PHP.ini Directives
Apply these directives inside your production environment `php.ini` file to harden the interpreter:

```ini
# Hide PHP signature in headers
expose_php = Off

# Disable remote URL execution in include scripts
allow_url_fopen = Off
allow_url_include = Off

# Disable unused and high-risk system function calls
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_multi_exec

# Protect session cookies from JavaScript inspection
session.cookie_httponly = 1
session.use_only_cookies = 1
session.cookie_samesite = "Strict"
```

### Directory Permissions (POSIX ACLs)
Follow the principle of least privilege. The web-exposed directory should be restricted:
1. Make the entire project directory read-only by the web server process:
   ```bash
   chown -R root:www-data /var/www/gsm-security
   find /var/www/gsm-security -type d -exec chmod 755 {} \;
   find /var/www/gsm-security -type f -exec chmod 644 {} \;
   ```
2. Grant write access **only** to the logs and backups folders:
   ```bash
   chmod -R 775 /var/www/gsm-security/logs
   chmod -R 775 /var/www/gsm-security/backups
   chown -R www-data:www-data /var/www/gsm-security/logs
   chown -R www-data:www-data /var/www/gsm-security/backups
   ```

### Web Server Rules
GSM Guard isolates core files under the root. Access to non-exposed directories (like `app/`, `logs/`, `backups/`) is protected in Apache via local configurations or Nginx server definitions:

#### Nginx Configuration Snippet:
```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/gsm-security/public;
    index index.php;

    # Secure routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Restrict direct execution of php files outside index.php
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    # Block access to hidden files and core directories
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 5. Performance Tuning & Optimizations

### PHP OPcache
Enable and tune the PHP OPcache engine to cache compiled bytecode in memory, drastically reducing script execution compilation overheads:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.save_comments=0
opcache.fast_shutdown=1
```

### Database Performance Tuning
The MySQL schema is indexed on critical columns. Ensure the database server allocates adequate buffers:
- **InnoDB Buffer Pool**: Set `innodb_buffer_pool_size` to 70-80% of system RAM on a dedicated database server.
- **Index Monitoring**: Periodically audit slow queries. The primary lookup tables (`sessions`, `users`, `activity_logs`, `rate_limits`) have optimized index hooks on lookup keys:
  - `sessions`: `idx_session_activity` on `last_activity` to speed up garbage collection.
  - `users`: Unique indexes on `username`, `email`, and `phone` for fast authentication lookup.
  - `rate_limits`: Unique index on `(ip_address, endpoint)` to speed up throttling queries.

---

## 6. Deployment Checklist

- [ ] **Checklist 1**: Web server SSL certificate is installed (TLS 1.2/1.3 enforced).
- [ ] **Checklist 2**: Environment diagnostics pass on `install.php`.
- [ ] **Checklist 3**: Database schema is loaded and seeds verified.
- [ ] **Checklist 4**: The setup lock file `logs/install.lock` is present.
- [ ] **Checklist 5**: `.env` variables have `APP_ENV=production` and `SESSION_SECURE=true`.
- [ ] **Checklist 6**: `APP_SECRET_KEY` is a unique cryptographically secure key.
- [ ] **Checklist 7**: File permissions are hardened (`logs/` and `backups/` are the only writable paths).
- [ ] **Checklist 8**: `display_errors` is set to `Off` in PHP settings.
