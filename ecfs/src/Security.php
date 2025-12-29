<?php
namespace EazeWebIT;

class Security {
    private static $nonce;

    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'cookie_samesite' => 'Strict',
            ]);
        }
        self::generateNonce();
        self::setSecurityHeaders();
    }

    private static function generateNonce() {
        if (self::$nonce === null) {
            self::$nonce = bin2hex(random_bytes(16));
        }
    }

    public static function getNonce() {
        if (self::$nonce === null) {
            self::generateNonce();
        }
        return self::$nonce;
    }

    public static function setSecurityHeaders() {
        if (!headers_sent()) {
            $nonce = self::getNonce();
            // We include the hashes provided in the error message to allow the specific blocked scripts.
            // We also use a nonce for other inline scripts.
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'nonce-$nonce' 'sha256-dVks1+4qkxhJVYy9bO7UKhd0mPeZ7xLAs1rfZCQ1XIc=' 'sha256-wfTMbI4zgsVuETB9U7VnbA3H3auqMr7HfVO/NfMEd7A=' https://cdn.tailwindcss.com; " .
                   "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; " .
                   "img-src 'self' data:; " .
                   "font-src 'self';";
            
            header("X-Content-Type-Options: nosniff");
            header("X-Frame-Options: SAMEORIGIN");
            header("X-XSS-Protection: 1; mode=block");
            header("Referrer-Policy: strict-origin-when-cross-origin");
            header("Content-Security-Policy: $csp");
        }
    }

    public static function generateCsrfToken() {
        self::initSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken($token) {
        self::initSession();
        return isset($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function logSecurityIncident($type, $details, $extra = null) {
        $db = Database::getInstance();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $db->prepare("INSERT INTO security_log (type, incident_details, extra_data, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type, $details, $extra ? json_encode($extra) : null, $ip]);
    }

    public static function checkRateLimit($identifier, $limit = 3, $seconds = 120) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM security_log WHERE type = 'login_attempt' AND ip_address = ? AND created_at > datetime('now', '-' || ? || ' seconds')");
        $stmt->execute([$identifier, $seconds]);
        return $stmt->fetchColumn() < $limit;
    }
}
