<?php
namespace EazeWebIT;

class Settings {
    public static function get(string $key, $default = null) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT Value FROM settings WHERE Key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    public static function getAll(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM settings");
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['Key']] = $row['Value'];
        }
        return $settings;
    }
}
