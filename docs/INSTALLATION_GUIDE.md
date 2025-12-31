# Installation & Deployment Guide

Follow these steps to set up the Eaze Contact Form System (ECFS) on your server.

## Prerequisites
- **Web Server**: Apache or Nginx.
- **PHP**: Version 8.0 or higher.
- **PHP Extensions**: `pdo_sqlite`, `openssl`, `fileinfo`.
- **Permissions**: Write access to the `ecfs/db/` and `ecfs/uploads/` directories.

## Step 1: Upload Files
Upload the entire project directory to your web server's root or a subdirectory.

```bash
/your-web-root/
├── ecfs/            # Core system files
└── index.php        # Landing page/demo (Optional)
```

## Step 2: Configure Permissions
Ensure the web server (e.g., `www-data`) has write permissions for the following:
- `ecfs/db/` (to create and write to the SQLite database)
- `ecfs/uploads/` (to store file attachments)

```bash
chmod -R 775 ecfs/db
chmod -R 775 ecfs/uploads
```

## Step 3: Run the Setup Wizard
Navigate to the setup page in your browser:
`http://yourdomain.com/ecfs/public/setup.php`

1. Provide an **Admin Username**.
2. Provide an **Admin Email**.
3. Set a strong **Admin Password**.
4. Click **Initialize System**.

This process will:
- Create the `app.sqlite` database.
- Execute the `schema.sql`.
- Create your admin account.
- Generate an `install.lock` file to prevent unauthorized resets.

## Step 4: Integration
To add the contact form to your website, include the JavaScript plugin and the necessary HTML structure.

### 1. Include the Script
Add the following script tag before the closing `</body>` tag:
```html
<script src="/ecfs/public/eaze_contact_form.js"></script>
```

### 2. Create the Form
Your form must have the `eaze-contact-form="true"` attribute.

```html
<form eaze-contact-form="true" eaze-contact-form-id="contact-page" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
    
    <label>Name</label>
    <input type="text" name="name" required>
    
    <label>Email</label>
    <input type="email" name="email" required>
    
    <label>Message</label>
    <textarea name="message" required></textarea>
    
    <button type="submit">Send</button>
</form>
```

## Step 5: Deployment Checklist
- [ ] Rename `ecfs/public/setup.php` or ensure `install.lock` exists.
- [ ] Update `SMTP` settings in the Admin Dashboard under **Settings**.
- [ ] Configure `upload_limit` and allowed file types in the dashboard.
- [ ] Test a submission to ensure emails are sent and data is saved.

---
Developed by [Eaze Web IT](https://eazewebit.com).
