# Usage Guide

This guide explains how to operate the ECFS Admin Panel and integrate the contact form into your website.

## Admin Panel Operations

### Dashboard
The Dashboard provides an immediate snapshot of system activity:
- **Total Submissions**: Cumulative count of all forms received.
- **Pending Actions**: Submissions awaiting review.
- **Recent Activity**: A table of the latest submissions with quick-view links.

### Managing Submissions
Navigate to **Manage Submissions** to handle data:
- **Viewing**: Click the "Preview" icon to see full submission details and download attachments.
- **Status Updates**: Change the status of a submission (e.g., from `Pending` to `Sent`) to track your progress.
- **Deletion**: Remove individual submissions. Note: This action is permanent.

### System Logs
- **Admin Logs**: Tracks actions performed by administrators (e.g., setting updates, status changes).
- **Security Logs**: View blocked attempts or suspicious activity identified by the system.

## Form Integration

To integrate the form into your front-end website, use the provided JavaScript bridge:

### 1. Include the Script
Add the following script tag to your website's `<head>` or before the closing `</body>` tag:
```html
<script src="https://yourdomain.com/ecfs/public/eaze_contact_form.js"></script>
```

### 2. Form Submission API
The system exposes a secure endpoint for submissions:
- **Endpoint**: `/ecfs/public/api/submit.php`
- **Method**: `POST`
- **Security**: Requires a valid CSRF token (handled automatically by the JS bridge or fetched via `/api/csrf.php`).

### 3. Handling Files
If your form includes file uploads, ensure the `enctype="multipart/form-data"` attribute is present on your HTML form.

## Managing Your Profile
Under the **Profile** section, administrators can:
- Update their email address.
- Change their password.
- View their current role and account status.
