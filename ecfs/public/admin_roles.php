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
            $name = $_POST['role_name'] ?? '';
            $desc = $_POST['role_description'] ?? '';
            $level = $_POST['level'] ?? 0;
            
            if ($name) {
                $stmt = $db->prepare("INSERT INTO roles (role_name, role_description, level) VALUES (?, ?, ?)");
                try {
                    $stmt->execute([$name, $desc, $level]);
                    $message = "Role added successfully.";
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $roleName = $_POST['role_name'] ?? '';
            if ($roleName !== 'admin' && $roleName !== 'user') {
                $stmt = $db->prepare("DELETE FROM roles WHERE role_name = ?");
                $stmt->execute([$roleName]);
                $message = "Role deleted.";
            } else {
                $error = "Cannot delete default roles.";
            }
        }
    }
}

$roles = $db->query("SELECT * FROM roles ORDER BY level DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <title>Role Management - EazeWebIT</title>
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
            <h1 class="text-3xl font-bold">Role Management</h1>
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
                    <h2 class="text-xl font-bold mb-4">Add New Role</h2>
                    <input type="hidden" name="action" value="add">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Role Name</label>
                        <input type="text" name="role_name" required class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Description</label>
                        <textarea name="role_description" class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white outline-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Level (0-100)</label>
                        <input type="number" name="level" value="0" class="w-full bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white outline-none">
                    </div>
                    <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 py-2 rounded-lg font-bold transition">Add Role</button>
                </form>
            </div>

            <div class="lg:col-span-2">
                <div class="glass rounded-2xl overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-white/5 border-b border-white/10">
                            <tr>
                                <th class="p-4 text-gray-400">Role</th>
                                <th class="p-4 text-center text-gray-400">Level</th>
                                <th class="p-4 text-right text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($roles as $role): ?>
                            <tr class="hover:bg-white/5 transition">
                                <td class="p-4">
                                    <div class="font-bold text-white"><?= htmlspecialchars($role['role_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?php 
                                        // Fix for literal \n strings showing up
                                        echo htmlspecialchars(str_replace('\\n', "\n", $role['role_description'])); 
                                    ?></div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="bg-white/10 px-2 py-1 rounded font-mono text-xs text-sky-400"><?= $role['level'] ?></span>
                                </td>
                                <td class="p-4 text-right">
                                    <?php if ($role['role_name'] !== 'admin' && $role['role_name'] !== 'user'): ?>
                                    <form method="POST" class="delete-role-form inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="role_name" value="<?= htmlspecialchars($role['role_name']) ?>">
                                        <button type="submit" class="text-red-400 hover:underline text-sm">Delete</button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-500 italic">System Role</span>
                                    <?php endif; ?>
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
            document.querySelectorAll('.delete-role-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to delete this role?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
