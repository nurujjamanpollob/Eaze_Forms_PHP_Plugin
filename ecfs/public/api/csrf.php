<?php
require_once __DIR__ . '/../../src/autoload.php';

use EazeWebIT\Security;
use EazeWebIT\Settings;

Security::initSession();

header('Content-Type: application/json');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';

if (!empty($origin)) {
    $parsedOrigin = parse_url($origin);
    $originHost = $parsedOrigin['host'] ?? '';
    if (isset($parsedOrigin['port'])) {
        $originHost .= ':' . $parsedOrigin['port'];
    }
    
    $allowed = false;
    if ($originHost === $host) {
        $allowed = true;
    } else {
        $allowedOriginsStr = Settings::get('allowed_origins', '');
        $allowedOrigins = array_filter(array_map('trim', explode(',', $allowedOriginsStr)));
        if (in_array($origin, $allowedOrigins)) {
            $allowed = true;
        }
    }
    
    if ($allowed) {
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
    } else {
        Security::logSecurityIncident('unauthorized_csrf_fetch', "Unauthorized origin attempted to fetch CSRF token: $origin");
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized origin']);
        exit;
    }
}

header("Vary: Origin");

echo json_encode(['csrf_token' => Security::generateCsrfToken()]);