<?php
namespace EazeWebIT;

use PDO;
use PDOException;
use Exception;

class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct() {
        $dbPath = __DIR__ . '/../db/app.sqlite';
        try {
            $this->connection = new PDO("sqlite:" . $dbPath);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->exec("PRAGMA foreign_keys = ON;");
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("A database error occurred. Please try again later.");
        }
    }

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
}
