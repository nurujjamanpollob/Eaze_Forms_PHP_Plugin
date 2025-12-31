<?php
namespace EazeWebIT;

/**
 * Class FileManager
 * Handles file system operations with security and error handling.
 */
class FileManager {
    /**
     * Deletes files from the storage based on relative paths.
     * 
     * @param array $paths Array of relative file paths (e.g., 'uploads/filename.ext')
     * @return array Results of the deletion process.
     */
    public static function deleteFiles(array $paths): array {
        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => []
        ];

        $baseDir = realpath(__DIR__ . '/../uploads/');
        if (!$baseDir) {
            // If uploads dir doesn't exist, nothing to delete
            return $results;
        }
        
        // Ensure base directory ends with a separator for strict prefix matching
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        foreach ($paths as $path) {
            if (empty($path)) continue;

            // Normalize path for the current OS
            $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
            
            // Security: Prevent directory traversal
            // 1. Resolve the absolute path
            // 2. Check if it exists and is a file
            // 3. Ensure it starts with the base directory
            
            $absolutePath = realpath(__DIR__ . '/../' . $normalizedPath);

            if ($absolutePath && is_file($absolutePath) && strpos($absolutePath, $baseDir) === 0) {
                try {
                    if (unlink($absolutePath)) {
                        $results['success'][] = $path;
                    } else {
                        $results['failed'][] = $path;
                    }
                } catch (\Exception $e) {
                    $results['failed'][] = $path;
                }
            } else {
                // File not found, not a file, or security violation (outside baseDir)
                $results['skipped'][] = $path;
            }
        }

        return $results;
    }

    /**
     * Extracts file paths from submission field values.
     * Supports both legacy string paths and new JSON metadata structures.
     * 
     * @param array $fields Array of field records from the database.
     * @return array List of unique file paths.
     */
    public static function extractPathsFromFields(array $fields): array {
        $paths = [];
        foreach ($fields as $field) {
            // Only process if field_type is 'file'
            if (isset($field['field_type']) && $field['field_type'] === 'file') {
                $value = $field['field_value'];
                if (empty($value)) continue;

                // Check if it's a JSON array or object
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    if (isset($decoded['path'])) {
                        // Single file metadata object
                        $paths[] = $decoded['path'];
                    } else {
                        // Array of files (either paths or metadata objects)
                        foreach ($decoded as $item) {
                            if (is_array($item) && isset($item['path'])) {
                                $paths[] = $item['path'];
                            } elseif (is_string($item)) {
                                $paths[] = $item;
                            }
                        }
                    }
                } else {
                    // Legacy: Single file path as a string
                    if (is_string($value)) {
                        $paths[] = $value;
                    }
                }
            }
        }
        return array_unique($paths);
    }
}
