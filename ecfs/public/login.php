<?php
require_once __DIR__ . '/../src/autoload.php';
use EazeWebIT\Security;
Security::initSession();
$csrfToken = Security::generateCsrfToken();
$nonce = Security::getNonce();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EazeWebIT</title>
    <script src="https://cdn.tailwindcss.com" nonce="<?= $nonce ?>"></script>
    <style>
        body { background: #0f172a; color: white; }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="glass p-8 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="text-center mb-8">
            <img src="/ecfs/public/assets/logo.png" alt="Logo" class="mx-auto h-16 mb-4">
            <h1 class="text-2xl font-bold text-sky-400">Admin Login</h1>
            <p class="text-gray-400">Manage your site data easily</p>
        </div>
        <form id="loginForm" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div>
                <label class="block text-sm font-medium mb-2">Username or Email</label>
                <input type="text" name="username" required class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Password</label>
                <input type="password" name="password" required class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 text-white font-bold py-2 rounded-lg transition">Sign In</button>
        </form>
        <div id="message" class="mt-4 text-center text-red-400 hidden"></div>
    </div>

    <?php include 'includes/copyright.php'; ?>

    <script nonce="<?= $nonce ?>">
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const res = await fetch('api/auth.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.href = 'dashboard.php';
            } else {
                const msg = document.getElementById('message');
                msg.textContent = data.message;
                msg.classList.remove('hidden');
                // If CSRF failed, we might need to refresh the page or token
                if (data.message.includes('Security validation')) {
                    setTimeout(() => window.location.reload(), 2000);
                }
            }
        });
    </script>
</body>
</html>