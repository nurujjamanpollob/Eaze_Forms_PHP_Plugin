<?php
require_once __DIR__ . '/../../src/autoload.php';

use EazeWebIT\Auth;
use EazeWebIT\Security;

Security::initSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Security validation failed (CSRF)']);
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$result = Auth::login($username, $password);
echo json_encode($result);
