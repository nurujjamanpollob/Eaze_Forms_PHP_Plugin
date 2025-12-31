<?php
namespace EazeWebIT;

use Exception;

class Submissions {
    /**
     * QUAL-04: Add type hinting and return types
     */
    public static function create(array $data, array $files = [], string $submittedBy = 'Guest'): array {
        $db = Database::getInstance();
        
        // QUAL-02: Move resource limits to settings table
        $maxFields = (int)Settings::get('max_submission_fields', 50);
        $maxKeyLength = (int)Settings::get('max_submission_key_length', 100);
        $maxValueLength = (int)Settings::get('max_submission_value_length', 10000);

        if (count($data) + count($files) > $maxFields) {
            return ['success' => false, 'message' => 'Too many fields submitted.'];
        }

        // SEC-02: Prevent overwriting system fields
        $reservedKeys = ['status', 'submitted_by', 'submission_id', 'created_at', 'csrf_token'];

        $db->beginTransaction();
        try {
            // Generate a unique submission_id
            $submissionId = (string)(time() . rand(1000, 9999));

            foreach ($data as $key => $value) {
                if (in_array($key, $reservedKeys)) continue;
                
                // Length limits
                if (strlen((string)$key) > $maxKeyLength) $key = substr((string)$key, 0, $maxKeyLength);
                if (strlen((string)$value) > $maxValueLength) $value = substr((string)$value, 0, $maxValueLength);

                $type = is_numeric($value) ? 'number' : 'text';
                $stmt = $db->prepare("INSERT INTO submissions (submission_id, field_key, field_value, field_type) VALUES (?, ?, ?, ?)");
                $stmt->execute([$submissionId, $key, $value, $type]);
            }

            foreach ($files as $key => $fileData) {
                if (in_array($key, $reservedKeys)) continue;
                if (strlen((string)$key) > $maxKeyLength) $key = substr((string)$key, 0, $maxKeyLength);
                $value = is_array($fileData) ? json_encode($fileData) : $fileData;
                
                if (strlen((string)$value) > $maxValueLength) $value = substr((string)$value, 0, $maxValueLength);

                $stmt = $db->prepare("INSERT INTO submissions (submission_id, field_key, field_value, field_type) VALUES (?, ?, ?, ?)");
                $stmt->execute([$submissionId, $key, $value, 'file']);
            }

            // Store who submitted this
            $stmt = $db->prepare("INSERT INTO submissions (submission_id, field_key, field_value, field_type) VALUES (?, 'submitted_by', ?, 'system')");
            $stmt->execute([$submissionId, $submittedBy]);

            // Initial Status - Fetch from settings
            $defaultStatus = Settings::get('default_status', 'pending');

            $stmt = $db->prepare("INSERT INTO submissions (submission_id, field_key, field_value, field_type) VALUES (?, 'status', ?, 'status')");
            $stmt->execute([$submissionId, $defaultStatus]);

            $stmt = $db->prepare("INSERT INTO logs (submission_id, action, details) VALUES (?, ?, ?)");
            $stmt->execute([$submissionId, 'create', "New submission received (Submitted by: $submittedBy)"]);

            $db->commit();
            return ['success' => true, 'submission_id' => $submissionId];
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function getAll(array $filters = []): array {
        $db = Database::getInstance();
        
        $where = [];
        $params = [];

        if (!empty($filters['status']) && $filters['status'] !== 'All Statuses') {
            $where[] = "submission_id IN (SELECT submission_id FROM submissions WHERE field_key = 'status' AND field_value = ?)";
            $params[] = strtolower($filters['status']);
        }

        if (!empty($filters['search'])) {
            $searchTerm = "%" . $filters['search'] . "%";
            $where[] = "submission_id IN (SELECT submission_id FROM submissions WHERE field_value LIKE ?)";
            $params[] = $searchTerm;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT submission_id, MAX(created_at) as created_at FROM submissions $whereSql GROUP BY submission_id ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $groups = $stmt->fetchAll();

        if (empty($groups)) return [];

        $submissionIds = array_column($groups, 'submission_id');
        $placeholders = implode(',', array_fill(0, count($submissionIds), '?'));

        $stmt = $db->prepare("SELECT submission_id, field_key, field_value, field_type FROM submissions WHERE submission_id IN ($placeholders)");
        $stmt->execute($submissionIds);
        $allFields = $stmt->fetchAll();

        $fieldMap = [];
        foreach ($allFields as $f) {
            $fieldMap[$f['submission_id']][$f['field_key']] = $f['field_value'];
        }

        $results = [];
        foreach ($groups as $group) {
            $sid = $group['submission_id'];
            $submission = ['submission_id' => $sid, 'created_at' => $group['created_at']];
            if (isset($fieldMap[$sid])) {
                $submission = array_merge($submission, $fieldMap[$sid]);
            }
            $results[] = $submission;
        }
        return $results;
    }

    public static function getById(string $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT field_key, field_value, field_type, created_at FROM submissions WHERE submission_id = ?");
        $stmt->execute([$id]);
        $fields = $stmt->fetchAll();
        if (!$fields) return null;

        $submission = ['submission_id' => $id, 'created_at' => $fields[0]['created_at'], 'fields' => $fields];
        foreach ($fields as $f) {
            if ($f['field_key'] === 'status') {
                $submission['status'] = $f['field_value'];
            }
            if ($f['field_key'] === 'submitted_by') {
                $submission['submitted_by'] = $f['field_value'];
            }
        }
        return $submission;
    }

    public static function updateStatus(string $id, string $status, ?int $userId = null): bool {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT id FROM submissions WHERE submission_id = ? AND field_key = 'status'");
        $stmt->execute([$id]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $db->prepare("UPDATE submissions SET field_value = ? WHERE submission_id = ? AND field_key = 'status'");
            $stmt->execute([$status, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO submissions (submission_id, field_key, field_value, field_type) VALUES (?, 'status', ?, 'status')");
            $stmt->execute([$id, $status]);
        }

        $stmt = $db->prepare("INSERT INTO logs (submission_id, action, performed_by, details) VALUES (?, 'update_status', ?, ?)");
        $stmt->execute([$id, $userId, "Status updated to: $status"]);
        
        return true;
    }

    public static function delete(string $id): bool {
        return self::bulkDelete([$id]);
    }

    public static function bulkUpdateStatus(array $ids, string $status, ?int $userId = null): bool {
        if (empty($ids)) return true;
        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE submissions SET field_value = ? WHERE field_key = 'status' AND submission_id IN ($placeholders)");
            $stmt->execute(array_merge([$status], $ids));

            foreach ($ids as $id) {
                $stmt = $db->prepare("INSERT INTO logs (submission_id, action, performed_by, details) VALUES (?, 'update_status', ?, ?)");
                $stmt->execute([$id, $userId, "Bulk status updated to: $status"]);
            }
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function bulkDelete(array $ids): bool {
        if (empty($ids)) return true;
        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $stmt = $db->prepare("SELECT field_value, field_type FROM submissions WHERE field_type = 'file' AND submission_id IN ($placeholders)");
        $stmt->execute($ids);
        $fileFields = $stmt->fetchAll();
        $filePaths = FileManager::extractPathsFromFields($fileFields);

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("DELETE FROM submissions WHERE submission_id IN ($placeholders)");
            $stmt->execute($ids);
            
            $stmt = $db->prepare("DELETE FROM logs WHERE submission_id IN ($placeholders)");
            $stmt->execute($ids);
            
            $db->commit();

            if (!empty($filePaths)) {
                FileManager::deleteFiles($filePaths);
            }

            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
}
