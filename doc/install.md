# Installation Guide

Follow these steps to set up the Eaze Contact Form System on your server.

## Prerequisites
- **Web Server**: Apache or Nginx.
- **PHP**: Version 8.0 or higher.
- **PHP Extensions**: `pdo_sqlite`, `openssl`, `mbstring`.
- **Database**: SQLite (built-in).

## Step-by-Step Setup

### 1. Upload Files
Upload the entire project directory to your web server's document root or a subdirectory.

### 2. Directory Permissions
Ensure the following directories are writable by the web server (e.g., `www-data`):
- `/ecfs/db/` (to create the SQLite database file)
- `/ecfs/uploads/` (to store submitted attachments)

### 3. Web Server Configuration
If using Apache, ensure `mod_rewrite` is enabled. The included `.htaccess` files handle security for the `db` and `uploads` folders.

### 4. Run the Setup Wizard
1. Navigate to `http://yourdomain.com/ecfs/public/setup.php` in your browser.
2. Provide the following details:
    - **Admin Username**: Your desired login name.
    - **Admin Email**: For system notifications and recovery.
    - **Admin Password**: A strong, secure password.
3. Click **Initialize System**.

### 5. Post-Installation
- Once successful, the system creates an `install.lock` file in the `/ecfs/db/` directory to prevent unauthorized re-runs of the setup.
- You will be redirected to the login page (`login.php`).
- **Security Recommendation**: Delete or restrict access to `public/setup.php` after installation.

## Manual Troubleshooting
- **Database Error**: Ensure the `ecfs/db` folder has `0755` or `0775` permissions.
- **Missing Extensions**: Check your `php.ini` to ensure `extension=pdo_sqlite` is uncommented.
