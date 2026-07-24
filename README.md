# Support Portal — Module 1: Core Framework

## Setup Instructions

### 1. Install Dependencies
```bash
composer install
```

### 2. Configure Environment
```bash
cp .env.example .env
# Edit .env with your database credentials, APP_URL, mail settings
```

### 3. Create Database & Run Schema
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS support_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p support_portal < database/schema.sql
```

### 4. Seed Sample Data
```bash
php database/seeds/DatabaseSeeder.php
```

### 5. Set Permissions
```bash
chmod -R 755 storage/
chmod -R 755 public/assets/uploads/
```

### 6. Configure Web Server

**Apache** — ensure `mod_rewrite` is enabled. Point DocumentRoot to `/public`.

**Nginx:**
```nginx
root /path/to/support-portal/public;
index index.php;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { fastcgi_pass unix:/run/php/php8.1-fpm.sock; include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; }
```

## Default Login
| Role        | Email                          | Password      |
|-------------|-------------------------------|---------------|
| Super Admin | admin@support-portal.com      | Admin@12345   |
| Employee    | john.smith@support.com        | Employee@123  |
| Client      | mike.johnson@acme.com         | Client@123    |

## Module 1 Files: 56 files | 4,617 PHP lines
