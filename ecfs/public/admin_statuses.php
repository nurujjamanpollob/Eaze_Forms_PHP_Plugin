<?php
require_once __DIR__ . '/../src/autoload.php';
use EazeWebIT\Database;
use EazeWebIT\Auth;
use EazeWebIT\Statuses;
use EazeWebIT\Security;

if (!Auth::isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

Security::initSession();
$nonce = Security::getNonce();
$csrfToken = Security::generateCsrfToken();

$db = Database::getInstance();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            $status = $_POST['status'] ?? '';
            $desc = $_POST['description'] ?? '';
            $color = $_POST['color'] ?? 'sky';
            
            if ($status) {
                $stmt = $db->prepare("INSERT INTO statuses (status, description, color) VALUES (?, ?, ?)");
                try {
                    $stmt->execute([strtolower($status), $desc, $color]);
                    $message = "Status label added.";
                } catch (\Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt = $db->prepare("DELETE FROM statuses WHERE id = ?");
                $stmt->execute([$id]);
                $message = "Status label removed.";
            }
        }
    }
}

$statuses = Statuses::all();
$colors = ['sky', 'yellow', 'green', 'red', 'blue', 'purple', 'gray', 'orange', 'pink'];
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <title>Status Management - EazeWebIT</title>
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
            <h1 class="text-3xl font-bold">Contact Status Labels</h1>
        </header>

        <?php if ($message): ?>
            <div class="bg-sky-500/20 text-sky-400 p-4 rounded-lg mb-6 border border-sky-500/30"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-500/20 text-red-400 p-4 rounded-lg mb-6 border border-red-500/30"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1">
                <form method="POST" class="glass p-6 rounded-2xl space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <h2 class="text-xl font-bold mb-4">Add Status Label</h2>
                    <input type="hidden" name="action" value="add">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status Name</label>
                        <input type="text" name="status" required placeholder="e.g. In Progress" class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Color Theme</label>
                        <select name="color" class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white outline-none">
                            <?php foreach ($colors as $c): ?>
                                <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Description</label>
                        <textarea name="description" class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white outline-none"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 py-2 rounded-lg font-bold transition">Add Label</button>
                </form>
            </div>

            <div class="lg:col-span-2">
                <div class="glass rounded-2xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-white/5 border-b border-white/10">
                            <tr>
                                <th class="p-4 text-gray-400">Status</th>
                                <th class="p-4 text-gray-400">Description</th>
                                <th class="p-4 text-right text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($statuses as $s): ?>
                            <tr class="hover:bg-white/5 transition">
                                <td class="p-4">
                                    <span class="px-2 py-1 text-xs rounded-full border font-bold uppercase <?= Statuses::getTailwindClasses($s['status']) ?>">
                                        <?= htmlspecialchars($s['status']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-sm text-gray-400">
                                    <?= htmlspecialchars($s['description']) ?>
                                </td>
                                <td class="p-4 text-right">
                                    <form method="POST" class="delete-status-form inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="text-red-400 hover:underline text-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/copyright.php'; ?>

    <script nonce="<?= $nonce ?>">
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-status-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to delete this status label?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>