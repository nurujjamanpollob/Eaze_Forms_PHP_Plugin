<?php
namespace EazeWebIT;

class Security {
    private static ?string $nonce = null;

    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            if (!$isSecure && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                $isSecure = true;
            }

            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => $isSecure,
                'cookie_samesite' => 'Strict',
            ]);
        }
        self::generateNonce();
        self::setSecurityHeaders();
    }

    private static function generateNonce(): void {
        if (self::$nonce === null) {
            self::$nonce = bin2hex(random_bytes(16));
        }
    }

    public static function getNonce(): string {
        if (self::$nonce === null) {
            self::generateNonce();
        }
        return self::$nonce;
    }

    /**
     * Determines if the current request is within the ECFS module scope.
     * 
     * @return bool
     */
    private static function isEcfsScope(): bool {
        $scriptPath = realpath($_SERVER['SCRIPT_FILENAME']);
        $ecfsDir = realpath(__DIR__ . '/..');
        
        // If the script is within the ecfs directory, it's in scope.
        return $scriptPath && strpos($scriptPath, $ecfsDir) === 0;
    }

    /**
     * Returns the strict CSP policy for internal ECFS modules.
     * 
     * @param string $nonce
     * @return string
     */
    private static function getStrictCsp(string $nonce): string {
        return "default-src 'self'; " .
               "script-src 'self' 'nonce-$nonce' " .
               "'sha256-dVks1+4qkxhJVYy9bO7UKhd0mPeZ7xLAs1rfZCQ1XIc=' " .
               "'sha256-wfTMbI4zgsVuETB9U7VnbA3H3auqMr7HfVO/NfMEd7A=' " .
               "'sha256-inSVo2oIIW3+vmRr3fba1FrWVEvfG7oz8OAaqSnWJk0=' " .
               "https://cdn.tailwindcss.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; " .
               "img-src 'self' data:; " .
               "font-src 'self';";
    }

    /**
     * Returns a relaxed CSP policy for external assets and integrations.
     * 
     * @return string
     */
    private static function getRelaxedCsp(): string {
        return "default-src * 'unsafe-inline' 'unsafe-eval' data: blob:;";
    }

    /**
     * Sets security headers based on the request scope.
     */
    public static function setSecurityHeaders(): void {
        if (!headers_sent()) {
            if (self::isEcfsScope()) {
                $nonce = self::getNonce();
                $csp = self::getStrictCsp($nonce);
                
                header("Content-Security-Policy: $csp");
                header("X-Frame-Options: SAMEORIGIN");
                header("X-XSS-Protection: 1; mode=block");
            } else {
                $csp = self::getRelaxedCsp();
                header("Content-Security-Policy: $csp");
                // X-Frame-Options and X-XSS-Protection are omitted or relaxed for external scope
            }
            
            // Common headers that are generally safe and recommended for all scopes
            header("X-Content-Type-Options: nosniff");
            header("Referrer-Policy: strict-origin-when-cross-origin");
        }
    }

    public static function generateCsrfToken(): string {
        self::initSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool {
        self::initSession();
        return isset($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function getClientIp(): string {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                foreach (explode(',', $_SERVER[$header]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        // Normalize IPv6 localhost to IPv4 for consistency
                        return ($ip === '::1') ? '127.0.0.1' : $ip;
                    }
                }
            }
        }

        return 'UNKNOWN';
    }


    public static function logSecurityIncident(string $type, string $details, $extra = null): void {
        $db = Database::getInstance();
        $ip = self::getClientIp();
        $stmt = $db->prepare("INSERT INTO security_log (type, incident_details, extra_data, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type, $details, $extra ? json_encode($extra) : null, $ip]);
    }

    public static function checkRateLimit(string $identifier, string $type = 'login_attempt', int $limit = 15, int $seconds = 180): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM security_log WHERE type = ? AND ip_address = ? AND created_at > datetime('now', '-' || ? || ' seconds')");
        $stmt->execute([$type, $identifier, $seconds]);
        return (int)$stmt->fetchColumn() < $limit;
    }
}
