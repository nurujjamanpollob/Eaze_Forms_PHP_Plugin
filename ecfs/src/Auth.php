<?php
namespace EazeWebIT;

class Auth {
    public static function login($username, $password) {
        $db = Database::getInstance();
        $ip = $_SERVER['REMOTE_ADDR'];

        if (!Security::checkRateLimit($ip)) {
            Security::logSecurityIncident('rate_limit_exceeded', 'Too many login attempts', ['ip' => $ip]);
            return ['success' => false, 'message' => 'Too many attempts. Try again later.'];
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && $user['locked']) {
            return ['success' => false, 'message' => 'Account is locked.'];
        }

        if ($user && password_verify($password, $user['password'])) {
            Security::initSession();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            Security::logSecurityIncident('login_success', "User $username logged in", ['ip' => $ip]);
            return ['success' => true];
        }

        Security::logSecurityIncident('login_attempt', "Failed login for $username", ['ip' => $ip]);
        
        // Lock account after 20 attempts logic would go here
        return ['success' => false, 'message' => 'Invalid credentials.'];
    }

    public static function check() {
        Security::initSession();
        return isset($_SESSION['user_id']);
    }

    public static function user() {
        Security::initSession();
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
    }

    public static function logout() {
        Security::initSession();
        session_destroy();
    }
    
    public static function isAdmin() {
        Security::initSession();
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
