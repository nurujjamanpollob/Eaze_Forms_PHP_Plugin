<?php
require_once __DIR__ . '/../src/autoload.php';
use EazeWebIT\Setup;
use EazeWebIT\Security;

if (Setup::isInitialized()) {
    header('Location: login.php');
    exit;
}

Security::initSession();
$nonce = Security::getNonce();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    try {
        if (Setup::run($user, $email, $pass)) {
            header('Location: login.php?setup=success');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Setup - EazeWebIT</title>
    <script src="https://cdn.tailwindcss.com" nonce="<?= $nonce ?>"></script>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen">
    <div class="bg-gray-800 p-8 rounded-xl shadow-xl w-full max-w-md">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-sky-400">System Setup</h1>
            <p class="text-gray-400 text-sm">Initialize your database and create an admin account.</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-200 p-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm mb-1 text-gray-300">Admin Username</label>
                <input type="text" name="username" required class="w-full bg-gray-700 border border-gray-600 rounded p-2 focus:outline-none focus:border-sky-500 transition">
            </div>
            <div>
                <label class="block text-sm mb-1 text-gray-300">Admin Email</label>
                <input type="email" name="email" required class="w-full bg-gray-700 border border-gray-600 rounded p-2 focus:outline-none focus:border-sky-500 transition">
            </div>
            <div>
                <label class="block text-sm mb-1 text-gray-300">Admin Password</label>
                <input type="password" name="password" required class="w-full bg-gray-700 border border-gray-600 rounded p-2 focus:outline-none focus:border-sky-500 transition">
            </div>
            <button type="submit" class="w-full bg-sky-600 hover:bg-sky-500 py-2 rounded font-bold transition duration-200">Initialize System</button>
        </form>
    </div>

    <?php include 'includes/copyright.php'; ?>
</body>
</html>
