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
        $allowedKeys = ['upload_limit', 'default_status', 'footer_text', 'admin_logo_url'];
        foreach ($_POST['settings'] as $key => $val) {
            if (in_array($key, $allowedKeys)) {
                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (Key, Value) VALUES (?, ?)");
                $stmt->execute([$key, $val]);
            }
        }
        $message = "Settings updated successfully!";
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
    <title>Settings - EazeWebIT</title>
    <script src="https://cdn.tailwindcss.com" nonce="<?= $nonce ?>"></script>
    <style>
        body { background: #0f172a; color: white; }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        select option { background: #1e293b; color: white; }
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <form method="POST" class="glass p-8 rounded-2xl space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <h2 class="text-xl font-bold border-b border-white/10 pb-4 mb-4">General Settings</h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Upload Limit (MB)</label>
                        <input type="number" name="settings[upload_limit]" value="<?= htmlspecialchars($settings['upload_limit'] ?? '10') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500 text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Default Submission Status</label>
                        <select name="settings[default_status]" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                            <?php foreach ($availableStatuses as $st): ?>
                                <option value="<?= htmlspecialchars($st['status']) ?>" <?= ($settings['default_status'] ?? 'pending') == $st['status'] ? 'selected' : '' ?>>
                                    <?= ucfirst(htmlspecialchars($st['status'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Footer Copyright Text</label>
                        <input type="text" name="settings[footer_text]" value="<?= htmlspecialchars($settings['footer_text'] ?? '© 2026 Eaze Web IT') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Admin Logo URL</label>
                        <input type="text" name="settings[admin_logo_url]" value="<?= htmlspecialchars($settings['admin_logo_url'] ?? '/ecfs/public/assets/logo.png') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white outline-none">
                    </div>
                    <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 py-3 rounded-xl font-bold transition">Save Configuration</button>
                </form>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="glass p-6 rounded-2xl">
                    <h3 class="font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="admin_statuses.php" class="text-sky-400 hover:underline">Manage Status Labels</a></li>
                        <li><a href="admin_logs.php?type=security" class="text-sky-400 hover:underline">Security Logs</a></li>
                        <li><a href="admin_logs.php?type=actions" class="text-sky-400 hover:underline">Audit Trail</a></li>
                    </ul>
                </div>
                
                <div class="bg-sky-500/10 border border-sky-500/20 p-6 rounded-2xl">
                    <h3 class="font-bold text-sky-400 mb-2">System Info</h3>
                    <div class="text-xs space-y-1 text-gray-400">
                        <p>Application: EazeWebIT Contact Form Service</p>
                        <p>Environment: Production</p>
                        <p>Upload Dir: /ecfs/uploads/</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/copyright.php'; ?>
</body>
</html>