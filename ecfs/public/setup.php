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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'];
    $email = $_POST['email'];
    $pass = $_POST['password'];
    
    if (Setup::run($user, $email, $pass)) {
        header('Location: login.php?setup=success');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Initial Setup - EazeWebIT</title>
    <script src="https://cdn.tailwindcss.com" nonce="<?= $nonce ?>"></script>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen">
    <div class="bg-gray-800 p-8 rounded-xl shadow-xl w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-sky-400">System Setup</h1>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm mb-1">Admin Username</label>
                <input type="text" name="username" required class="w-full bg-gray-700 border-none rounded p-2">
            </div>
            <div>
                <label class="block text-sm mb-1">Admin Email</label>
                <input type="email" name="email" required class="w-full bg-gray-700 border-none rounded p-2">
            </div>
            <div>
                <label class="block text-sm mb-1">Admin Password</label>
                <input type="password" name="password" required class="w-full bg-gray-700 border-none rounded p-2">
            </div>
            <button type="submit" class="w-full bg-sky-600 py-2 rounded font-bold">Initialize System</button>
        </form>
    </div>

    <?php include 'includes/copyright.php'; ?>
</body>
</html>