<?php
require_once __DIR__ . '/../src/autoload.php';
use EazeWebIT\Auth;
use EazeWebIT\Security;

// SEC-02: Require POST request and valid CSRF token for logout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        Auth::logout();
    }
}

header('Location: login.php');
exit;
