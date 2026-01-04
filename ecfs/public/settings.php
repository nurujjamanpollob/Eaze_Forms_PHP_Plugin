<?php
require_once __DIR__ . '/../src/autoload.php';
use EazeWebIT\Database;
use EazeWebIT\Auth;
use EazeWebIT\Statuses;
use EazeWebIT\Security;

if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

Security::initSession();
$nonce = Security::getNonce();

$db = Database::getInstance();
$isAdmin = Auth::isAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } else {
        $allowedKeys = [
            'upload_limit', 'default_status', 'footer_text', 'admin_logo_url', 'allowed_origins',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_name', 'smtp_from_email',
            'admin_recipient_email', 'enable_confirmation_email', 'enable_admin_notification',
            'dashboard_pagination_limit', 'manage_submissions_pagination_limit',
            'user_email_template', 'admin_email_template'
        ];
        $db->beginTransaction();
        try {
            if (isset($_POST['settings']) && is_array($_POST['settings'])) {
                foreach ($_POST['settings'] as $key => $val) {
                    if (in_array($key, $allowedKeys)) {
                        if ($key === 'admin_logo_url') {
                            // Validate URL: must be relative or from trusted domain
                            if (!empty($val) && !filter_var($val, FILTER_VALIDATE_URL) && strpos($val, '/') !== 0) {
                                 throw new \Exception("Invalid Logo URL. Must be a relative path starting with '/' or a valid absolute URL.");
                            }
                        }
                        if ($key === 'allowed_origins') {
                            // Basic validation for origins (comma separated)
                            $origins = explode(',', $val);
                            foreach ($origins as $origin) {
                                $origin = trim($origin);
                                if (!empty($origin) && !filter_var($origin, FILTER_VALIDATE_URL)) {
                                    throw new \Exception("Invalid Origin: " . htmlspecialchars($origin));
                                }
                            }
                        }
                        $stmt = $db->prepare("INSERT OR REPLACE INTO settings (Key, Value) VALUES (?, ?)");
                        $stmt->execute([$key, $val]);
                    }
                }
            }
            
            // Handle checkboxes if not present in POST (unchecked checkboxes are not sent)
            $checkboxes = ['enable_confirmation_email', 'enable_admin_notification'];
            foreach ($checkboxes as $cb) {
                if (!isset($_POST['settings'][$cb])) {
                    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (Key, Value) VALUES (?, ?)");
                    $stmt->execute([$cb, '0']);
                }
            }
            
            // Log the action
            $userId = $_SESSION['user_id'] ?? null;
            $logStmt = $db->prepare("INSERT INTO logs (action, performed_by, details) VALUES (?, ?, ?)");
            $logStmt->execute(['update_settings', $userId, 'System settings updated by admin']);
            
            $db->commit();
            $message = "Settings updated successfully!";
        } catch (\Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

$stmt = $db->query("SELECT * FROM settings");
$settings = [];
foreach ($stmt->fetchAll() as $row) {
    $settings[$row['Key']] = $row['Value'];
}

$availableStatuses = Statuses::all();
$csrfToken = Security::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - EazeWebIT</title>
    <script src="https://cdn.tailwindcss.com" nonce="<?= $nonce ?>"></script>
    <link rel="stylesheet" href="assets/responsive.css">
    <style>
        body { background: #0f172a; color: white; }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        select option { background: #1e293b; color: white; }
        textarea { resize: vertical; min-height: 120px; }
    </style>
</head>
<body class="text-gray-200 min-h-screen flex">
    
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold">System Configuration</h1>
        </header>

        <?php if (isset($message)): ?>
            <div class="bg-green-500/20 text-green-400 p-4 rounded-lg mb-6 border border-green-500/30"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-500/20 text-red-400 p-4 rounded-lg mb-6 border border-red-500/30"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- General & Pagination Settings -->
                <div class="glass p-8 rounded-2xl space-y-6">
                    <h2 class="text-xl font-bold border-b border-white/10 pb-4 mb-4">General & Display</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Upload Limit (MB)</label>
                            <input type="number" name="settings[upload_limit]" value="<?= htmlspecialchars($settings['upload_limit'] ?? '10') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500 text-white outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Default Status</label>
                            <select name="settings[default_status]" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                                <?php foreach ($availableStatuses as $st): ?>
                                    <option value="<?= htmlspecialchars($st['status']) ?>" <?= ($settings['default_status'] ?? 'pending') == $st['status'] ? 'selected' : '' ?>>
                                        <?= ucfirst(htmlspecialchars($st['status'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Dashboard Limit</label>
                            <input type="number" name="settings[dashboard_pagination_limit]" value="<?= htmlspecialchars($settings['dashboard_pagination_limit'] ?? '6') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Manage Page Limit</label>
                            <input type="number" name="settings[manage_submissions_pagination_limit]" value="<?= htmlspecialchars($settings['manage_submissions_pagination_limit'] ?? '10') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Footer Copyright Text</label>
                        <input type="text" name="settings[footer_text]" value="<?= htmlspecialchars($settings['footer_text'] ?? '© 2026 Eaze Web IT') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Admin Logo URL</label>
                        <input type="text" name="settings[admin_logo_url]" value="<?= htmlspecialchars($settings['admin_logo_url'] ?? '/ecfs/public/assets/logo.png') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Allowed Origins</label>
                        <input type="text" name="settings[allowed_origins]" value="<?= htmlspecialchars($settings['allowed_origins'] ?? '') ?>" placeholder="https://example.com" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                    </div>
                </div>

                <!-- SMTP Settings -->
                <div class="glass p-8 rounded-2xl space-y-6">
                    <h2 class="text-xl font-bold border-b border-white/10 pb-4 mb-4">SMTP Configuration</h2>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-400 mb-2">SMTP Host</label>
                            <input type="text" name="settings[smtp_host]" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" placeholder="smtp.mailtrap.io" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Port</label>
                            <input type="number" name="settings[smtp_port]" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">SMTP Username</label>
                        <input type="text" name="settings[smtp_user]" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">SMTP Password</label>
                        <input type="password" name="settings[smtp_pass]" value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">From Name</label>
                            <input type="text" name="settings[smtp_from_name]" value="<?= htmlspecialchars($settings['smtp_from_name'] ?? 'EazeWebIT Notifications') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">From Email</label>
                            <input type="email" name="settings[smtp_from_email]" value="<?= htmlspecialchars($settings['smtp_from_email'] ?? 'noreply@example.com') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                        </div>
                    </div>
                </div>

                <!-- Email Templates Section -->
                <div class="glass p-8 rounded-2xl space-y-6 lg:col-span-2">
                    <h2 class="text-xl font-bold border-b border-white/10 pb-4 mb-4">Email Templates</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">User Confirmation Template (HTML)</label>
                            <textarea name="settings[user_email_template]" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none font-mono text-sm"><?= htmlspecialchars($settings['user_email_template'] ?? '') ?></textarea>
                            <div class="flex items-center mt-4 space-x-3">
                                <input type="checkbox" id="enable_confirmation" name="settings[enable_confirmation_email]" value="1" <?= ($settings['enable_confirmation_email'] ?? '0') == '1' ? 'checked' : '' ?> class="w-4 h-4 bg-white/10 border-white/20 rounded text-sky-600 focus:ring-sky-500">
                                <label for="enable_confirmation" class="text-sm font-medium text-gray-400">Enable user confirmation email</label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Admin Notification Template (HTML)</label>
                            <textarea name="settings[admin_email_template]" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none font-mono text-sm"><?= htmlspecialchars($settings['admin_email_template'] ?? '') ?></textarea>
                            <div class="space-y-4 mt-4">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" id="enable_admin_notification" name="settings[enable_admin_notification]" value="1" <?= ($settings['enable_admin_notification'] ?? '0') == '1' ? 'checked' : '' ?> class="w-4 h-4 bg-white/10 border-white/20 rounded text-sky-600 focus:ring-sky-500">
                                    <label for="enable_admin_notification" class="text-sm font-medium text-gray-400">Enable admin notification</label>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-400 mb-1">Admin Recipient Email</label>
                                    <input type="email" name="settings[admin_recipient_email]" value="<?= htmlspecialchars($settings['admin_recipient_email'] ?? '') ?>" placeholder="admin@example.com" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Template Guide -->
                    <div class="bg-sky-500/10 border border-sky-500/20 p-6 rounded-xl mt-6">
                        <h3 class="text-sky-400 font-bold mb-2 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Dynamic Template Guide
                        </h3>
                        <p class="text-sm text-gray-400 mb-4">Use double curly braces to inject submission data into your templates. All values are automatically sanitized.</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-black/20 p-3 rounded-lg">
                                <code class="text-sky-300 text-xs font-bold">{{submission_id}}</code>
                                <p class="text-[10px] text-gray-500 mt-1">Unique ID of the submission</p>
                            </div>
                            <div class="bg-black/20 p-3 rounded-lg">
                                <code class="text-sky-300 text-xs font-bold">{{submitted_by}}</code>
                                <p class="text-[10px] text-gray-500 mt-1">Username or 'Guest'</p>
                            </div>
                            <div class="bg-black/20 p-3 rounded-lg">
                                <code class="text-sky-300 text-xs font-bold">{{form_data}}</code>
                                <p class="text-[10px] text-gray-500 mt-1">HTML list of all form fields</p>
                            </div>
                            <div class="bg-black/20 p-3 rounded-lg">
                                <code class="text-sky-300 text-xs font-bold">{{field_name}}</code>
                                <p class="text-[10px] text-gray-500 mt-1">Any specific field (e.g., {{email}}, {{message}})</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="lg:col-span-2 flex justify-end mb-5-rem-mobile">
                    <button type="submit" class="w-full md:w-auto bg-sky-600 hover:bg-sky-500 px-12 py-4 rounded-xl font-bold transition shadow-lg shadow-sky-500/20">Save All Configuration</button>
                </div>
            </div>
        </form>
    </main>

    <?php include 'includes/copyright.php'; ?>
</body>
</html>
