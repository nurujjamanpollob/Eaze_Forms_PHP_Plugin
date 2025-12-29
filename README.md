# Eaze Contact Form System (ECFS)

A professional, self-contained PHP/SQLite backend for managing contact form submissions. Developed by **Eaze Web IT** (https://eazewebit.com).

Features an embeddable JavaScript widget, an EAV-based data model, and a modern glassmorphism admin dashboard.

## 🚀 Features
- **Zero-Config Setup**: Automatic database initialization on first run.
- **EAV Data Model**: Flexible form fields without schema changes.
- **Secure File Uploads**: Support for multiple files, images, and videos.
- **Admin Dashboard**: Responsive, dark-themed UI built with Tailwind CSS.
- **Security**: CSRF protection, rate limiting, and audit logging.
- **Embeddable Widget**: Single JS file to connect any HTML form to the backend.

## 🛠️ Installation & Setup

1. **System Requirements**:
   - PHP 8.2 or higher.
   - SQLite3 extension enabled in PHP.
   - A web server (Apache, Nginx, or PHP's built-in server).

2. **Deployment**:
   - Place the `ecfs` folder in your project directory.
   - **IMPORTANT**: Set your web server's document root to the `ecfs/public` folder. This ensures that sensitive files in `src/`, `db/`, and `uploads/` are not directly accessible via the browser.
   - Ensure the `db/` and `uploads/` directories are writable by the web server.

3. **Initialization**:
   - Navigate to `http://your-domain.com/setup.php` (if docroot is set to `public`).
   - Follow the wizard to create your admin account.
   - **SECURITY**: After setup is complete, the system creates an `install.lock` file in the `db/` folder to prevent re-initialization.

4. **Running with PHP built-in server**:
   ```bash
   cd ecfs/public
   php -S localhost:8000
   ```
   Then visit `http://localhost:8000/setup.php`.

## 📦 How to Embed the Form

Add the following to any HTML page:

1. **Include the script**:
   ```html
   <script src="/eaze_contact_form.js"></script>
   ```

2. **Create your form**:
   ```html
   <form eaze-contact-form=\"true\" eaze-contact-form-id=\"contact_v1\" method=\"POST\">
       <input type=\"text\" name=\"full_name\" placeholder=\"Name\" required>
       <input type=\"email\" name=\"email\" placeholder=\"Email\" required>
       <textarea name=\"message\" placeholder=\"Your message\"></textarea>
       <input type=\"file\" name=\"attachments[]\" multiple>
       <button type=\"submit\">Send</button>
   </form>
   ```

## 🧪 Testing
Run the automated test script via CLI:
```bash
php tests/test_submission.php
```

## 📂 Project Structure
- `assets/`: Static assets (logos, icons).
- `db/`: SQLite database, schema, and installation lock.
- `public/`: Web-accessible entry points and API. **(Set as Document Root)**
- `src/`: Core PHP logic and classes.
- `uploads/`: Secure storage for submitted files.
- `tests/`: Functional test scripts.

## 🔒 Security Best Practices
- **Document Root**: Always point your web server to the `public/` directory.
- **File Permissions**: Keep `db/` and `uploads/` writable but restricted.
- **Nginx Users**: Explicitly deny access to `uploads`, `db`, and `src` folders in your site configuration.
- **SSL/TLS**: Always serve the application over HTTPS.

---
© 2026 **Eaze Web IT** (https://eazewebit.com). All rights reserved.
