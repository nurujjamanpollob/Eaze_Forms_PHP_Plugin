# Eaze Contact Form System (ECFS) - Implementation Guide

## 1. Overview
The Eaze Contact Form System (ECFS) is a lightweight, secure, and flexible backend solution for managing website contact form submissions. It is designed to be self-contained, using PHP 8.2+ and SQLite3, making it easy to deploy on any standard web server without complex database configurations.

## 2. Architectural Design

### 2.1 Backend Architecture
The system follows a modular object-oriented approach:
- **Core Logic (`src/`)**: Contains classes for Authentication, Database management, Security, and Submission handling.
- **EAV Data Model**: The system uses an Entity-Attribute-Value model to store form submissions. This allows it to handle any number of form fields dynamically without requiring database schema changes for new forms.
- **SQLite Database**: A single file-based database (`db/app.sqlite`) provides portability and zero-configuration setup.

### 2.2 Frontend Architecture
- **Admin Dashboard**: Built with a modern "Glassmorphism" design using Tailwind CSS.
- **Embeddable Widget**: A standalone JavaScript file (`public/eaze_contact_form.js`) that intercepts form submissions and sends them to the API via AJAX/Fetch.

## 3. Core Components

### 3.1 `EazeWebIT\Database`
A singleton class that manages the PDO connection to the SQLite database. It ensures only one connection is active during a request.

### 3.2 `EazeWebIT\Submissions`
Handles the storage and retrieval of form data. It maps flat form POST data into the EAV structure (`submissions` and `submission_meta` tables).

### 3.3 `EazeWebIT\UploadHandler`
A secure utility for processing file uploads. It validates MIME types, enforces file size limits, and renames files with unique hashes to prevent directory traversal and execution attacks.

### 3.4 `EazeWebIT\Security`
Provides essential security layers:
- **CSRF Protection**: Token-based validation for all state-changing requests.
- **Session Management**: Secure session initialization with HttpOnly and SameSite attributes.
- **Rate Limiting**: (Implemented in API endpoints) prevents brute-force submissions.
- **Audit Logging**: Tracks admin actions and system events.

## 4. Implementation Workflow

### 4.1 Deployment
1. Upload the `ecfs` folder to your server.
2. **Crucial**: Set your web server's document root to `ecfs/public/`.
3. Ensure `ecfs/db/` and `ecfs/uploads/` are writable by the server (e.g., `chmod 775`).

### 4.2 Initialization
Access `https://your-domain.com/setup.php`. The system will:
1. Create the SQLite database file if it doesn't exist.
2. Execute `db/schema.sql` to build the tables.
3. Prompt for admin credentials.
4. Create an `install.lock` file to prevent subsequent access to the setup script.

### 4.3 Form Integration
To connect an HTML form to ECFS:
1. Include the script: `<script src="/eaze_contact_form.js"></script>`.
2. Add attributes to your `<form>` tag:
   - `eaze-contact-form="true"`: Enables the script.
   - `eaze-contact-form-id="unique_id"`: Identifies the form in the dashboard.
3. The script handles the `submit` event, manages file uploads via FormData, and displays success/error messages.

## 5. Security Best Practices
- **Isolation**: By setting the document root to `public/`, the `src/`, `db/`, and `uploads/` directories are physically inaccessible via URL.
- **HTACCESS**: The `uploads/` directory contains an `.htaccess` file to disable PHP execution (for Apache servers).
- **SSL**: Always use HTTPS to protect data in transit.

## 6. Maintenance
- **Database Backups**: Simply copy the `db/app.sqlite` file.
- **Updates**: Replace files in `src/` and `public/` (excluding `assets/` or custom settings).

---
Developed by **Eaze Web IT** (https://eazewebit.com)
