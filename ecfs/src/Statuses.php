<?php
namespace EazeWebIT;

class Statuses {
    public static function all(): array {
        $db = Database::getInstance();
        return $db->query("SELECT * FROM statuses ORDER BY status ASC")->fetchAll();
    }

    public static function getNames(): array {
        $statuses = self::all();
        return array_column($statuses, 'status');
    }

    public static function getColorMap(): array {
        $statuses = self::all();
        $map = [];
        foreach ($statuses as $s) {
            $map[$s['status']] = $s['color'] ?? 'sky';
        }
        return $map;
    }

    public static function exists(string $status): bool {
        $db = Database::getInstance();
        // Use COLLATE NOCASE for case-insensitive check if the DB doesn't have it, 
        // but adding it to schema is better. Still, let's be safe here.
        $stmt = $db->prepare("SELECT id FROM statuses WHERE status = ? COLLATE NOCASE");
        $stmt->execute([$status]);
        return (bool)$stmt->fetch();
    }

    public static function getTailwindClasses(string $status, ?array $colorMap = null): string {
        if (!$colorMap) {
            $colorMap = self::getColorMap();
        }
        // Ensure case-insensitive lookup in the map
        $status = strtolower($status);
        $color = 'sky';
        foreach ($colorMap as $s => $c) {
            if (strtolower($s) === $status) {
                $color = $c;
                break;
            }
        }
        
        $colors = [
            'yellow' => 'bg-yellow-500/20 text-yellow-500 border-yellow-500/30',
            'green'  => 'bg-green-500/20 text-green-500 border-green-500/30',
            'red'    => 'bg-red-500/20 text-red-500 border-red-500/30',
            'blue'   => 'bg-blue-500/20 text-blue-500 border-blue-500/30',
            'sky'    => 'bg-sky-500/20 text-sky-400 border-sky-500/30',
            'purple' => 'bg-purple-500/20 text-purple-500 border-purple-500/30',
            'gray'   => 'bg-gray-500/20 text-gray-400 border-gray-500/30',
            'orange' => 'bg-orange-500/20 text-orange-500 border-orange-500/30',
            'pink'   => 'bg-pink-500/20 text-pink-500 border-pink-500/30',
        ];

        return $colors[$color] ?? $colors['sky'];
    }
}
