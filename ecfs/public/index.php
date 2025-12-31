<?php
require_once __DIR__ . '/../src/autoload.php';

use EazeWebIT\Setup;
use EazeWebIT\Auth;
use EazeWebIT\Security;

Security::initSession();

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Automatically determine base path
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = str_replace('/index.php', '', $scriptName);
$route = str_replace($basePath, '', $requestUri);

// Setup check
if (!Setup::isInitialized() && $route !== '/setup.php') {
    header('Location: ' . $basePath . '/setup.php');
    exit;
}

// Simple Router
switch ($route) {
    case '':
    case '/':
    case '/dashboard.php':
        if (!Auth::check()) { header('Location: ' . $basePath . '/login.php'); exit; }
        require 'dashboard.php';
        break;
    case '/login.php':
        require 'login.php';
        break;
    case '/setup.php':
        require 'setup.php';
        break;
    case '/api/submit.php':
        require 'api/submit.php';
        break;
    case '/api/auth.php':
        require 'api/auth.php';
        break;
    case '/logout.php':
        Auth::logout();
        header('Location: ' . $basePath . '/login.php');
        break;
    default:
        // Check if it's a file in public
        $requestedPath = __DIR__ . $route;
        $realPath = realpath($requestedPath);
        $publicDir = realpath(__DIR__);

        // Ensure the file exists and is within the public directory to prevent path traversal
        if ($realPath && strpos($realPath, $publicDir) === 0 && is_file($realPath)) {
            return false; // Let server handle static file
        }
        http_response_code(404);
        echo "404 Not Found";
        break;
}
