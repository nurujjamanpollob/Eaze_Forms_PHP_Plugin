<?php
namespace EazeWebIT;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $dbPath = __DIR__ . '/../db/app.sqlite';
        try {
            $this->connection = new PDO("sqlite:" . $dbPath);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->exec("PRAGMA foreign_keys = ON;");
            
            $this->ensureSchemaUpdated();
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("A database error occurred. Please try again later.");
        }
    }

    private function ensureSchemaUpdated() {
        // Check if color column exists in statuses table
        $stmt = $this->connection->query("PRAGMA table_info(statuses)");
        $columns = $stmt->fetchAll();
        $hasColor = false;
        foreach ($columns as $column) {
            if ($column['name'] === 'color') {
                $hasColor = true;
                break;
            }
        }

        if (!$hasColor) {
            $this->connection->exec("ALTER TABLE statuses ADD COLUMN color TEXT DEFAULT 'sky'");
            // Update default colors for existing statuses
            $this->connection->exec("UPDATE statuses SET color = 'yellow' WHERE status = 'pending'");
            $this->connection->exec("UPDATE statuses SET color = 'green' WHERE status = 'sent'");
            $this->connection->exec("UPDATE statuses SET color = 'red' WHERE status = 'error'");
        }

        // Check if ip_address column exists in security_log table
        $stmt = $this->connection->query("PRAGMA table_info(security_log)");
        $columns = $stmt->fetchAll();
        $hasIp = false;
        foreach ($columns as $column) {
            if ($column['name'] === 'ip_address') {
                $hasIp = true;
                break;
            }
        }

        if (!$hasIp) {
            $this->connection->exec("ALTER TABLE security_log ADD COLUMN ip_address TEXT");
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_security_ip ON security_log (ip_address)");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
}
