<?php
namespace EazeWebIT;

use PDOException;
use Exception;

class Setup {
    private static $lockFile = __DIR__ . '/../db/install.lock';

    /**
     * Checks if the system has already been initialized.
     */
    public static function isInitialized(): bool {
        return file_exists(self::$lockFile);
    }

    /**
     * Runs the initial database setup and creates the admin user.
     * 
     * @param string $adminUser
     * @param string $adminEmail
     * @param string $adminPass
     * @return bool
     * @throws Exception
     */
    public static function run(string $adminUser, string $adminEmail, string $adminPass): bool {
        if (self::isInitialized()) {
            return false;
        }

        $db = Database::getInstance();
        $schemaPath = __DIR__ . '/../db/schema.sql';
        
        if (!file_exists($schemaPath)) {
            throw new Exception("Schema file not found.");
        }

        $schema = file_get_contents($schemaPath);
        
        try {
            $db->beginTransaction();

            // Execute schema queries
            // Split by semicolon to handle multiple statements safely
            $queries = explode(';', $schema);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $db->exec($query);
                }
            }

            // Check if admin already exists to prevent duplicates
            $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' OR username = ? OR email = ? LIMIT 1");
            $stmt->execute([$adminUser, $adminEmail]);
            
            if (!$stmt->fetch()) {
                $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, role, is_verified) VALUES (?, ?, ?, 'admin', 1)");
                $stmt->execute([$adminUser, $adminEmail, $hashedPass]);
            }

            $db->commit();

            // Create lock file to prevent re-initialization
            if (file_put_contents(self::$lockFile, date('Y-m-d H:i:s')) === false) {
                throw new Exception("Failed to create installation lock file. Check directory permissions.");
            }
            
            return true;
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Setup Error: " . $e->getMessage());
            throw new Exception("Database initialization failed: " . $e->getMessage());
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
