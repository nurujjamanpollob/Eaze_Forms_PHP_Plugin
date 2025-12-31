<?php
require_once __DIR__ . '/../src/autoload.php';
use EazeWebIT\Auth;
use EazeWebIT\Security;

if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

Security::initSession();
$nonce = Security::getNonce();
$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword !== $confirmPassword) {
            $error = "New passwords do not match.";
        } else {
            $result = Auth::changePassword($user['id'], $currentPassword, $newPassword);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

$csrfToken = Security::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <title>Profile - EazeWebIT</title>
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
            <h1 class="text-3xl font-bold">User Profile</h1>
        </header>

        <?php if (isset($message)): ?>
            <div class="bg-green-500/20 text-green-400 p-4 rounded-lg mb-6 border border-green-500/30"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-500/20 text-red-400 p-4 rounded-lg mb-6 border border-red-500/30"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="max-w-2xl">
            <div class="glass p-8 rounded-2xl mb-8">
                <h2 class="text-xl font-bold border-b border-white/10 pb-4 mb-6">Account Information</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400">Username</label>
                        <div class="text-lg font-semibold"><?= htmlspecialchars($user['username']) ?></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400">Role</label>
                        <div class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-sky-500/20 text-sky-400 border border-sky-500/30">
                            <?= strtoupper(htmlspecialchars($user['role'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" class="glass p-8 rounded-2xl space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <h2 class="text-xl font-bold border-b border-white/10 pb-4 mb-4">Change Password</h2>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">Current Password</label>
                    <input type="password" name="current_password" required class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500 text-white outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">New Password</label>
                    <input type="password" name="new_password" required class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500 text-white outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-2">Confirm New Password</label>
                    <input type="password" name="confirm_password" required class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-500 text-white outline-none">
                </div>

                <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 py-3 rounded-xl font-bold transition">Update Password</button>
            </form>
        </div>
    </main>

    <?php include 'includes/copyright.php'; ?>
</body>
</html>
