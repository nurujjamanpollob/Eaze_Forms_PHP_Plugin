# Eaze Contact Form System (ECFS)

**A professional, self-contained PHP/SQLite backend for managing contact form submissions.**

Developed by **[Eaze Web IT](https://eazewebit.com)**, ECFS is designed to be a "drop-in" solution for developers who need a secure, reliable backend for contact forms without the complexity of setting up heavy frameworks or database servers.

## 📚 Documentation Index

Detailed guides are located in the `docs/` directory. Refer to these files for specific configurations:

| Document | Description |
| :--- | :--- |
| **[Feature List](docs/FEATURE_LIST.md)** | A comprehensive breakdown of all system capabilities, including the EAV data model and security features. |
| **[Installation Guide](docs/INSTALLATION_GUIDE.md)** | Step-by-step instructions for server requirements, file permissions, and the setup wizard. |
| **[Technical Overview](docs/TECHNICAL_OVERVIEW.md)** | Deep dive into the architecture, security layers, and code structure. |
| **[Customization Guide](docs/CUSTOMIZATION_GUIDE.md)** | Instructions for developers to extend logic, add webhooks, style the dashboard, and handle JS events. |

## 🚀 Key Features

*   **Zero-Config Database:** Powered by **SQLite**, requiring no MySQL/PostgreSQL server setup.
*   **Dynamic Data Model (EAV):** Add unlimited fields to your HTML forms without ever running database migrations. The system automatically detects and stores new inputs.
*   **Enterprise-Grade Security:**
    *   Built-in **CSRF Protection**.
    *   **Rate Limiting** to block spam and brute-force attacks.
    *   **Content Security Policy (CSP)** headers.
    *   Secure file upload handling (stored outside public root).
*   **Powerful Admin Dashboard:**
    *   Real-time submission overview.
    *   Status management (Pending, Resolved, etc.).
    *   Audit logs for admin actions.
    *   Role-Based Access Control (RBAC).
*   **Email Notifications:** SMTP integration for admin alerts and user confirmation auto-replies.

## 🛠️ Tech Stack

*   **Backend:** PHP 8.0+
*   **Database:** SQLite 3 (File-based)
*   **Frontend:** Vanilla JavaScript (AJAX)
*   **Dashboard Styling:** Tailwind CSS

## ⚡ Quick Start Guide

### 1. Prerequisites
Ensure your server has **PHP 8.0+** installed with the following extensions: `pdo_sqlite`, `openssl`, `fileinfo`.

### 2. Installation
1.  Upload the `ecfs` directory to your web server.
2.  Set write permissions for the database and upload folders:
    ```bash
    chmod -R 775 ecfs/db
    chmod -R 775 ecfs/uploads
    ```
3.  Navigate to the setup wizard in your browser:
    `http://yourdomain.com/ecfs/public/setup.php`
4.  Create your admin account and initialize the system.

### 3. Frontend Integration
Include the plugin script and add the required attributes to your HTML form.

```html
<!-- 1. Include the script -->
<script src="/ecfs/public/eaze_contact_form.js"></script>

<!-- 2. Create the form -->
<form eaze-contact-form="true" enctype="multipart/form-data">
    <!-- CSRF Token (Required) -->
    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCsrfToken(); ?>">
    
    <input type="text" name="name" placeholder="Your Name" required>
    <input type="email" name="email" placeholder="Your Email" required>
    <textarea name="message" placeholder="How can we help?"></textarea>
    
    <button type="submit">Send Message</button>
</form>
