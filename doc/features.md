# Application Features

The **Eaze Contact Form System (ECFS)** is a robust, lightweight, and secure contact form management solution designed for developers and site administrators.

## Core Functionalities

### 1. Responsive Admin Dashboard
- **Unified Overview**: Real-time statistics on total submissions, pending requests, and system logs.
- **Mobile-First Design**: Fully responsive interface built with Tailwind CSS, ensuring accessibility across desktops, tablets, and smartphones.
- **Visual Analytics**: Status-coded submission summaries for quick assessment.

### 2. Submission Management
- **Centralized Storage**: All form submissions are stored in a local SQLite database for high performance and zero external dependencies.
- **Detailed View**: Access full submission data, including metadata and custom fields.
- **Status Workflow**: Track the lifecycle of a submission with statuses like `Pending`, `Sent`, and `Error`.
- **Bulk Actions**: Efficiently manage large volumes of data with pagination and deletion capabilities.

### 3. Advanced Notification System
- **SMTP Integration**: Support for secure SMTP servers (TLS/SSL) to ensure reliable email delivery.
- **HTML Email Templates**: Fully customizable HTML templates for both user confirmations and admin notifications.
- **Dynamic Placeholders**: Inject submission data into emails using double curly braces (e.g., `{{submission_id}}`, `{{form_data}}`, `{{field_name}}`).
- **Conditional Notifications**: Toggle user and admin emails independently via the settings panel.

### 4. Security & Access Control
- **Role-Based Access Control (RBAC)**: Distinct permissions for `Admin` and `User` roles.
- **CSRF Protection**: Comprehensive protection against Cross-Site Request Forgery on all state-changing actions.
- **Security Logging**: Automatic tracking of suspicious activities and system incidents with IP logging.
- **Input Sanitization**: Multi-layer sanitization for all user-submitted data to prevent XSS and SQL injection.
- **CORS Support**: Configurable "Allowed Origins" to restrict form submissions to authorized domains.

### 5. System Customization
- **Flexible Configuration**: Modify upload limits, default statuses, and pagination sizes directly from the UI.
- **Branding**: Update the admin logo and footer copyright text to match your organization's identity.
- **Pagination Control**: Independent settings for dashboard and management page pagination limits.

### 6. Technical Architecture
- **PHP 8.x Optimized**: Leverages modern PHP features for performance and security.
- **SQLite Backend**: Portable and easy to back up without the overhead of a full RDBMS.
- **Modular Source**: Clean, object-oriented structure (`src/` directory) for easy extensibility.
