# Customization Guide

The ECFS provides extensive customization options through the **System Configuration** page in the Admin Panel.

## General Settings

### Branding & UI
- **Admin Logo URL**: Provide a relative path (e.g., `/assets/logo.png`) or an absolute URL to update the dashboard logo.
- **Footer Copyright Text**: Modify the text displayed at the bottom of the admin pages.

### Display & Limits
- **Upload Limit (MB)**: Set the maximum allowed size for file uploads through the form.
- **Default Status**: Choose the initial status assigned to new submissions (e.g., `pending`).
- **Dashboard Limit**: Number of recent submissions to display on the main dashboard.
- **Manage Page Limit**: Number of submissions per page in the management view.

## SMTP & Notifications

To enable automated emails, configure the SMTP settings:

1. **SMTP Host**: Your mail server address (e.g., `smtp.gmail.com`).
2. **Port**: Usually `587` for TLS or `465` for SSL.
3. **SMTP Credentials**: Username and Password for authentication.
4. **From Name/Email**: The identity used when sending notifications.
5. **Admin Recipient Email**: The address where admin notifications will be sent.

## Email Templates (HTML)

ECFS supports dynamic HTML templates for communication.

### Available Placeholders
| Placeholder | Description |
| :--- | :--- |
| `{{submission_id}}` | The unique ID of the submission. |
| `{{submitted_by}}` | The username of the submitter or 'Guest'. |
| `{{form_data}}` | An HTML list containing all submitted fields and values. |
| `{{field_name}}` | Any specific form field (e.g., `{{email}}`, `{{subject}}`). |

### User Confirmation Template
Enable this to send an automated "Thank You" email to the user.
**Example:**
```html
<h2>Thank you for your submission!</h2>
<p>We have received your form (Ref: #{{submission_id}}).</p>
```

### Admin Notification Template
Enable this to alert administrators of new submissions.
**Example:**
```html
<h2>New Submission Received</h2>
<p><strong>From:</strong> {{email}}</p>
<div>{{form_data}}</div>
```

## Security Customization

### Allowed Origins (CORS)
To prevent unauthorized domains from submitting to your API:
1. Navigate to **Settings**.
2. Enter a comma-separated list of authorized URLs in the **Allowed Origins** field (e.g., `https://yourdomain.com, https://app.yourdomain.com`).
3. Leave empty to allow all origins (not recommended for production).
