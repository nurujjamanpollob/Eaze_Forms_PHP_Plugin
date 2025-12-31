# Developer Customization Guide

This guide is for developers looking to extend or modify the Eaze Contact Form System (ECFS).

## 1. Extending the Data Model
Because ECFS uses an EAV model, you don't need to change the database to add new fields.

**To add a new field:**
Simply add an input to your HTML form with a unique `name` attribute.
```html
<input type="text" name="customer_loyalty_id" placeholder="Loyalty ID">
```
The backend will automatically detect this field and store it in the `submissions` table.

## 2. Modifying the Submission Logic
If you need to perform custom validation or trigger external APIs (like a CRM) upon submission, modify `ecfs/public/api/submit.php`.

**Example: Adding a Webhook Trigger**
```php
// In ecfs/public/api/submit.php
$result = Submissions::create($_POST, $_FILES, 'Web Form');

if ($result['success']) {
    // Custom logic: Trigger a Slack Webhook
    file_get_contents("https://hooks.slack.com/services/...", false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/json',
            'content' => json_encode(['text' => 'New Lead Received!'])
        ]
    ]));
}
```

## 3. Styling the Admin Dashboard
The admin dashboard uses **Tailwind CSS** via CDN. To customize the look:
1. Open any file in `ecfs/public/` (e.g., `dashboard.php`).
2. Modify the Tailwind classes in the HTML.
3. For custom CSS, add a `<style>` block in `ecfs/public/includes/sidebar.php` to apply it globally across the dashboard.

## 4. Customizing Email Templates
Email logic is located in `ecfs/src/Mailer.php`. Currently, it uses basic HTML strings.

**To customize the notification email:**
Edit the `sendAdminNotification` or `sendUserConfirmation` methods in `Mailer.php`.

```php
public static function sendAdminNotification($data) {
    $subject = \"New Submission: \" . ($data['subject'] ?? 'No Subject');
    $body = \"<h1>New Lead</h1><p>From: {$data['name']}</p>\";
    // ... add more custom HTML here
    return self::send(Settings::get('admin_recipient_email'), $subject, $body);
}
```

## 5. Adding New Submission Statuses
Statuses are stored in the `statuses` table. You can add more via the database or by adding an SQL insert to `schema.sql` before installation.

**Available Colors (Tailwind-based):**
- `blue`, `green`, `yellow`, `red`, `purple`, `gray`, `sky`.

## 6. JavaScript Events
The `eaze_contact_form.js` plugin dispatches a custom event `eazeContactFormSubmit` after a submission. You can listen to this to trigger custom frontend behavior.

```javascript
window.addEventListener('eazeContactFormSubmit', function(e) {
    if (e.detail.status === 'success') {
        console.log('Submission ID:', e.detail.submission_id);
        // Redirect to a thank you page
        window.location.href = '/thank-you';
    }
});
```

---
Developed by [Eaze Web IT](https://eazewebit.com).
