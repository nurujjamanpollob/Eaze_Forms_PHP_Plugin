# Eaze Contact Form System (ECFS)

[![Version](https://img.shields.io/badge/version-1.1.0-blue.svg)](doc/changes.md)
[![PHP](https://img.shields.io/badge/php-%5E8.0-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Database](https://img.shields.io/badge/database-SQLite-003b57.svg)](https://www.sqlite.org/)

**Eaze Contact Form System (ECFS)** is a professional, self-hosted, and lightweight backend solution designed to manage website form submissions. Built with a focus on security, portability, and ease of use, ECFS bridges the gap between static HTML forms and complex CRM systems.

---

## 🚀 Key Features

- **Responsive Admin Dashboard**: A mobile-first interface built with Tailwind CSS for managing submissions on any device.
- **Dynamic Form Handling**: Uses an EAV (Entity-Attribute-Value) database model to support arbitrary form fields without schema changes.
- **Advanced Notifications**: SMTP-powered HTML email templates with dynamic data injection (e.g., `{{field_name}}`).
- **Robust Security**:
    - **CSRF Protection**: Native token validation for all state-changing requests.
    - **Content Security Policy (CSP)**: Strict headers with dynamic nonces to mitigate XSS.
    - **Security Logging**: Real-time tracking of suspicious activities and IP-based rate limiting.
- **File Management**: Secure handling and storage of attachments with automated cleanup.
- **Zero-Config Database**: Utilizes SQLite 3 for maximum portability and effortless backups.

---

## 🏗️ System Architecture

ECFS is architected as a modular PHP application following modern best practices:

- **Core Logic (`ecfs/src/`)**: Decoupled, object-oriented modules for Authentication, Database access (Singleton), Mailer (SMTP), and Security.
- **Data Layer**: A centralized SQLite database (`app.sqlite`) ensures the entire system is contained within a single directory.
- **Security Layer**: A centralized `Security` class manages sessions with secure flags (`HttpOnly`, `Secure`, `SameSite=Strict`), validates CSRF tokens, and enforces CSP policies.
- **Frontend Bridge**: A lightweight Vanilla JS script (`eaze_contact_form.js`) provides a seamless integration layer for any frontend website.

---

## 🛠️ Technical Specifications

- **Backend**: PHP 8.0+
- **Database**: SQLite 3
- **Frontend**: Tailwind CSS 3.x, Vanilla JavaScript
- **Required PHP Extensions**:
    - `pdo_sqlite`: Database connectivity.
    - `openssl`: Secure SMTP and encryption.
    - `mbstring`: Multi-byte string handling.
    - `json`: Data serialization.

---

## 📥 Installation Process

1. **Upload**: Copy the `ecfs/` folder and `index.php` to your web server.
2. **Permissions**: Ensure `/ecfs/db/` and `/ecfs/uploads/` are writable by the web server.
3. **Initialize**: Navigate to `http://yourdomain.com/ecfs/public/setup.php`.
4. **Configure**: Provide admin credentials and initialize the system.
5. **SMTP Setup**: Log in to the dashboard and configure your SMTP settings in the **Settings** panel.

*For more details, see the [Installation Guide](doc/install.md).*

---

## 🔌 Integration

To connect your website's contact form to ECFS:

1. **Include Bridge**: Add `<script src="/ecfs/public/eaze_contact_form.js"></script>` to your page.
2. **Form Setup**: Ensure your HTML form has an `id` and appropriate `name` attributes for inputs.
3. **Submit**: The bridge automatically handles CSRF fetching and submission to the `/api/submit.php` endpoint.

---

## 📖 Documentation

Explore the modular documentation for deep dives into specific areas:

- 🚀 **[Installation Guide](doc/install.md)**: Detailed setup and troubleshooting.
- ✨ **[Features List](doc/features.md)**: Full breakdown of system capabilities.
- ⚙️ **[Customization](doc/customization.md)**: Modifying themes, settings, and email templates.
- 📖 **[Usage Guide](doc/usage.md)**: Admin panel operations and API integration.
- 🕒 **[Changelog](doc/changes.md)**: History of updates and improvements.

---

© 2026 Eaze Web IT. Distributed under the MIT License.
