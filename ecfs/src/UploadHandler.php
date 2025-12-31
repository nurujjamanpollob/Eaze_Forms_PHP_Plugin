<?php
namespace EazeWebIT;

/**
 * Class UploadHandler
 * Handles secure file uploads, validation, and storage.
 */
class UploadHandler {
    private static $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'mp4', 'webm'];
    private static $allowedMimeTypes = [
        'image/jpeg', 'image/png', 'image/gif', 
        'application/pdf', 'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain', 'application/zip', 'video/mp4', 'video/webm'
    ];

    /**
     * Processes uploaded files from $_FILES.
     * Supports single and multiple file uploads (arrays).
     * 
     * @param array $files The $_FILES array or a subset of it.
     * @return array Map of field names to file data (or arrays of file data).
     * @throws \Exception If validation fails.
     */
    public static function handle($files) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new \Exception("Failed to create upload directory.");
            }
        }

        $limitMB = (int)Settings::get('upload_limit', 10);
        $limitBytes = $limitMB * 1024 * 1024;
        
        // Total size limit for all files in one request (prevent resource exhaustion)
        $totalSize = 0;
        foreach ($files as $file) {
            if (is_array($file['name'])) {
                foreach ($file['size'] as $size) $totalSize += $size;
            } else {
                $totalSize += $file['size'];
            }
        }
        
        // Global limit for total request size (e.g., 100MB)
        $globalLimitMB = (int)Settings::get('global_upload_limit', 100);
        if ($totalSize > ($globalLimitMB * 1024 * 1024)) {
             throw new \Exception("Total upload size exceeds the global limit of {$globalLimitMB}MB.");
        }

        $processed = [];
        foreach ($files as $name => $file) {
            // PHP's $_FILES structure is different for multiple files (name[])
            if (is_array($file['name'])) {
                $fileObjects = [];
                $count = count($file['name']);
                for ($i = 0; $i < $count; $i++) {
                    if (empty($file['name'][$i])) continue;
                    
                    $fileData = [
                        'name'     => $file['name'][$i],
                        'type'     => $file['type'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error'    => $file['error'][$i],
                        'size'     => $file['size'][$i]
                    ];
                    
                    $result = self::processSingleFile($fileData, $uploadDir, $limitBytes, $limitMB);
                    if ($result) {
                        $fileObjects[] = $result;
                    }
                }
                if (!empty($fileObjects)) {
                    // If name ends with [], remove it for the key in the returned array
                    $cleanName = str_replace('[]', '', $name);
                    $processed[$cleanName] = $fileObjects;
                }
            } else {
                if (empty($file['name'])) continue;
                
                $result = self::processSingleFile($file, $uploadDir, $limitBytes, $limitMB);
                if ($result) {
                    $processed[$name] = $result;
                }
            }
        }
        return $processed;
    }

    /**
     * Validates and moves a single uploaded file.
     * 
     * @param array $file Single file data from $_FILES.
     * @param string $uploadDir Destination directory.
     * @param int $limitBytes Max size in bytes.
     * @param int $limitMB Max size in MB (for error message).
     * @return array|null The file data (path, original_name, etc) or null.
     * @throws \Exception If validation fails.
     */
    private static function processSingleFile($file, $uploadDir, $limitBytes, $limitMB) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                throw new \Exception("File '{$file['name']}' exceeds the maximum allowed size.");
            }
            if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;

            throw new \Exception("Error uploading file '{$file['name']}'. Code: " . $file['error']);
        }

        if ($file['size'] > $limitBytes) {
            throw new \Exception("File '{$file['name']}' exceeds the limit of {$limitMB}MB.");
        }

        $originalName = $file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedExtensions)) {
            throw new \Exception("File extension '.{$ext}' is not allowed.");
        }

        // Verify MIME type using finfo for security
        if (!class_exists('\finfo')) {
             // Fallback if finfo is not available, though it should be
             $mimeType = $file['type'];
        } else {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
        }

        if (!in_array($mimeType, self::$allowedMimeTypes)) {
            throw new \Exception("File type '{$mimeType}' is not allowed for file '{$file['name']}'.");
        }

        // Secure storage: Use a cryptographically secure random name
        $newName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . $newName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Return relative path for database storage and metadata
            return [
                'path' => 'uploads/' . $newName,
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'size' => $file['size']
            ];
        }
        
        return null;
    }
}
