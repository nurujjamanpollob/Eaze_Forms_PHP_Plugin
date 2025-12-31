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
$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } else {
        $action = $_POST['action'] ?? '';
        $userId = $_POST['user_id'] ?? null;

        if ($action === 'toggle_lock' && $userId) {
            // Prevent locking self
            if ($userId != $_SESSION['user_id']) {
                $stmt = $db->prepare("UPDATE users SET locked = 1 - locked WHERE id = ?");
                $stmt->execute([$userId]);
                $message = "User lock status updated.";
            } else {
                $error = "You cannot lock your own account.";
            }
        } elseif ($action === 'delete' && $userId) {
            // Prevent deleting self
            if ($userId != $_SESSION['user_id']) {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $message = "User deleted.";
            } else {
                $error = "You cannot delete your own account.";
            }
        } elseif ($action === 'update_role' && $userId) {
            // Prevent changing self role
            if ($userId != $_SESSION['user_id']) {
                $newRole = $_POST['role'] ?? 'user';
                $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$newRole, $userId]);
                $message = "User role updated.";
            } else {
                $error = "You cannot change your own role.";
            }
        }
    }
}


$users = $db->query("SELECT id, username, email, role, locked, created_at FROM users")->fetchAll();
$roles = $db->query("SELECT role_name FROM roles")->fetchAll(PDO::FETCH_COLUMN);
$csrfToken = Security::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <title>User Management - EazeWebIT</title>
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
            <div>
                <h1 class="text-3xl font-bold">User Management</h1>
                <p class="text-gray-400">Manage system users and permissions</p>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="bg-sky-500/20 text-sky-400 p-4 rounded-lg mb-6 border border-sky-500/30"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-500/20 text-red-400 p-4 rounded-lg mb-6 border border-red-500/30"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="glass rounded-2xl overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-white/5 border-b border-white/10">
                    <tr>
                        <th class="p-4">User</th>
                        <th class="p-4">Role</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Created At</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-white/5 transition">
                        <td class="p-4">
                            <div class="font-bold text-white"><?= htmlspecialchars($user['username']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($user['email']) ?></div>
                        </td>
                        <td class="p-4">
                            <form method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                                <select name="role" class="role-select bg-white/10 border border-white/20 rounded px-2 py-1 text-sm text-white outline-none <?= $user['id'] == $_SESSION['user_id'] ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= htmlspecialchars($role) ?>" <?= $user['role'] === $role ? 'selected' : '' ?>><?= ucfirst(htmlspecialchars($role)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td class="p-4">
                            <?php if ($user['locked']): ?>
                                <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs rounded-full border border-red-500/30">Locked</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full border border-green-500/30">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-sm text-gray-400"><?= htmlspecialchars($user['created_at']) ?></td>
                        <td class="p-4 text-right space-x-2">
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                                <input type="hidden" name="action" value="toggle_lock">
                                <button type="submit" class="text-sm text-orange-400 hover:underline">
                                    <?= $user['locked'] ? 'Unlock' : 'Lock' ?>
                                </button>
                            </form>
                            <form method="POST" class="delete-user-form inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="text-sm text-red-400 hover:underline">Delete</button>
                            </form>
                            <?php else: ?>
                                <span class="text-xs text-gray-500 italic">Self (Protected)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php include 'includes/copyright.php'; ?>

    <script nonce="<?= $nonce ?>">
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit role change
            document.querySelectorAll('.role-select').forEach(select => {
                select.addEventListener('change', function() {
                    this.form.submit();
                });
            });

            // Confirm delete
            document.querySelectorAll('.delete-user-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to delete this user?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
