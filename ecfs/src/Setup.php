<?php
namespace EazeWebIT;

class Setup {
    private static $lockFile = __DIR__ . '/../db/install.lock';

    public static function isInitialized() {
        return file_exists(self::$lockFile);
    }

    public static function run($adminUser, $adminEmail, $adminPass) {
        if (self::isInitialized()) {
            return false;
        }

        $db = Database::getInstance();
        $schema = file_get_contents(__DIR__ . '/../db/schema.sql');
        $db->exec($schema);

        // Check if admin exists
        $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, role, is_verified) VALUES (?, ?, ?, 'admin', 1)");
            $stmt->execute([$adminUser, $adminEmail, $hashedPass]);
        }

        // Create lock file
        file_put_contents(self::$lockFile, date('Y-m-d H:i:s'));
        
        return true;
    }
}
