<?php
namespace EazeWebIT;
use EazeWebIT\Security;

class Auth {
    public static function login(string $username, string $password): array {
        $db = Database::getInstance();
        $ip = Security::getClientIp() ?? 'unknown';

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
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            Security::logSecurityIncident('login_success', "User $username logged in", ['ip' => $ip]);
            return ['success' => true];
        }

        Security::logSecurityIncident('login_attempt', "Failed login for $username", ['ip' => $ip]);
        
        return ['success' => false, 'message' => 'Invalid credentials.'];
    }

    public static function check(): bool {
        Security::initSession();
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array {
        Security::initSession();
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
    }

    public static function logout(): void {
        Security::initSession();
        session_destroy();
    }
    
    public static function isAdmin(): bool {
        Security::initSession();
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public static function changePassword(int $userId, string $currentPassword, string $newPassword): array {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Incorrect current password.'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters long.'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->execute([$hashedPassword, $userId]);

        // Log the action
        $logStmt = $db->prepare("INSERT INTO logs (action, performed_by, details) VALUES (?, ?, ?)");
        $logStmt->execute(['password_change', $userId, 'User changed their password']);

        return ['success' => true, 'message' => 'Password updated successfully.'];
    }
}
