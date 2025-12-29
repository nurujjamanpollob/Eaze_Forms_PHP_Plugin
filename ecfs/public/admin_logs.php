<?php
require_once __DIR__ . '/../src/autoload.php';
use EazeWebIT\Database;
use EazeWebIT\Auth;
use EazeWebIT\Security;

if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

Security::initSession();
$nonce = Security::getNonce();

$db = Database::getInstance();
$type = $_GET['type'] ?? 'security';

if ($type === 'security') {
    $logs = $db->query("SELECT * FROM security_log ORDER BY created_at DESC LIMIT 100")->fetchAll();
    $title = "Security Incidents";
} else {
    $logs = $db->query("SELECT l.*, u.username FROM logs l LEFT JOIN users u ON l.performed_by = u.id ORDER BY l.created_at DESC LIMIT 100")->fetchAll();
    $title = "Action Logs";
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <title>System Logs - EazeWebIT</title>
    <script src="https://cdn.tailwindcss.com" nonce="<?= $nonce ?>"></script>
    <style>
        body { background: #0f172a; color: white; }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-gray-200 min-h-screen flex">
    
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold">System Logs</h1>
                <div class="mt-2 space-x-4">
                    <a href="?type=security" class="<?= $type === 'security' ? 'text-sky-400 font-bold border-b-2 border-sky-400 pb-1' : 'text-gray-400 hover:text-gray-200' ?>">Security Logs</a>
                    <a href="?type=actions" class="<?= $type === 'actions' ? 'text-sky-400 font-bold border-b-2 border-sky-400 pb-1' : 'text-gray-400 hover:text-gray-200' ?>">Action Logs</a>
                </div>
            </div>
        </header>

        <div class="glass rounded-2xl overflow-hidden">
            <div class="p-4 bg-white/5 border-b border-white/10 flex justify-between items-center">
                <h2 class="font-bold text-white"><?= htmlspecialchars($title) ?></h2>
                <span class="text-xs text-gray-500">Showing last 100 entries</span>
            </div>
            <table class="w-full text-left">
                <thead class="bg-white/5 border-b border-white/10 text-xs uppercase text-gray-500">
                    <?php if ($type === 'security'): ?>
                    <tr>
                        <th class="p-4">Time</th>
                        <th class="p-4">Type</th>
                        <th class="p-4">Details</th>
                        <th class="p-4">Extra Data</th>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <th class="p-4">Time</th>
                        <th class="p-4">User</th>
                        <th class="p-4">Action</th>
                        <th class="p-4">Submission ID</th>
                    </tr>
                    <?php endif; ?>
                </thead>
                <tbody class="divide-y divide-white/5 text-sm">
                    <?php foreach ($logs as $log): ?>
                    <tr class="hover:bg-white/5 transition">
                        <td class="p-4 text-gray-400 whitespace-nowrap"><?= htmlspecialchars($log['created_at']) ?></td>
                        
                        <?php if ($type === 'security'): ?>
                        <td class="p-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border <?= strpos($log['type'], 'success') !== false ? 'bg-green-500/10 text-green-400 border-green-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20' ?>">
                                <?= htmlspecialchars($log['type']) ?>
                            </span>
                        </td>
                        <td class="p-4 text-white"><?= htmlspecialchars($log['incident_details']) ?></td>
                        <td class="p-4 text-xs font-mono text-gray-500"><?= htmlspecialchars($log['extra_data']) ?></td>
                        
                        <?php else: ?>
                        <td class="p-4 font-bold text-white"><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                        <td class="p-4 text-white"><?= htmlspecialchars($log['action']) ?></td>
                        <td class="p-4 text-sky-400 font-mono">#<?= htmlspecialchars($log['submission_id']) ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4" class="p-8 text-center text-gray-500 italic">No log entries found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php include 'includes/copyright.php'; ?>
</body>
</html>