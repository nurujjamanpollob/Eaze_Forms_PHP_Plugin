# Technical Overview: Eaze Contact Form System (ECFS)

Developed by **Eaze Web IT** ([https://eazewebit.com](https://eazewebit.com)), the Eaze Contact Form System (ECFS) is a lightweight, secure, and highly flexible backend solution for managing website contact form submissions.

## Architectural Philosophy

ECFS is built on the principles of simplicity, security, and extensibility. It avoids heavy dependencies and complex configurations, providing a "drop-in" backend that works with any frontend stack.

### Core Technologies
- **Backend**: PHP 8.0+
- **Database**: SQLite 3 (Serverless, file-based)
- **Frontend Integration**: Vanilla JavaScript (AJAX-based)
- **Styling (Dashboard)**: Tailwind CSS (via CDN)

## Key Components

### 1. Data Model (EAV Pattern)
Unlike traditional contact forms that require a fixed database schema for every field, ECFS uses an **Entity-Attribute-Value (EAV)** model. 
- **Submissions Table**: Stores each form field as a separate row linked by a unique `submission_id`.
- **Benefit**: Allows developers to add, remove, or change form fields on the frontend without ever modifying the database schema.

### 2. Security Layer
Security is baked into the core of ECFS:
- **CSRF Protection**: Every submission is validated against a unique session-based token.
- **Content Security Policy (CSP)**: Implements strict CSP headers for the admin dashboard and relaxed policies for external integrations.
- **Rate Limiting**: IP-based rate limiting prevents brute-force attacks and spam submissions.
- **Secure File Uploads**: Validates file types, sizes, and stores them outside the public web root with obfuscated names.

### 3. Modular Source (`src/`)
The application logic is organized into specialized classes:
- `Auth.php`: Handles user authentication and session management.
- `Database.php`: Manages the SQLite connection using the Singleton pattern.
- `Submissions.php`: Core logic for creating, retrieving, and managing form data.
- `Mailer.php`: Handles SMTP-based email notifications.
- `Security.php`: Centralizes CSRF, CSP, and rate-limiting logic.

### 4. Admin Dashboard
A responsive administrative interface allows for:
- Real-time submission monitoring.
- Status management (e.g., Pending, Sent, Error).
- System-wide settings configuration (SMTP, Upload limits, etc.).
- User and Role management.

---
© 2024 [Eaze Web IT](https://eazewebit.com). All rights reserved.
