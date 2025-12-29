<?php
namespace EazeWebIT;

class UploadHandler {
    private static $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'mp4', 'webm'];
    private static $allowedMimeTypes = [
        'image/jpeg', 'image/png', 'image/gif', 
        'application/pdf', 'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain', 'application/zip', 'video/mp4', 'video/webm'
    ];

    public static function handle($files) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new \Exception("Failed to create upload directory.");
            }
        }

        $limitMB = (int)Settings::get('upload_limit', 10);
        $limitBytes = $limitMB * 1024 * 1024;

        $processed = [];
        foreach ($files as $name => $file) {
            if (empty($file['name'])) continue;

            if (is_array($file['name'])) {
                // Multiple files
                $paths = [];
                foreach ($file['name'] as $i => $filename) {
                    if (empty($filename)) continue;
                    $fileData = [
                        'name' => $file['name'][$i],
                        'type' => $file['type'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i],
                        'size' => $file['size'][$i]
                    ];
                    $paths[] = self::processSingleFile($fileData, $uploadDir, $limitBytes, $limitMB);
                }
                $processed[$name] = array_filter($paths);
            } else {
                // Single file
                $path = self::processSingleFile($file, $uploadDir, $limitBytes, $limitMB);
                if ($path) {
                    $processed[$name] = $path;
                }
            }
        }
        return $processed;
    }

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

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedExtensions)) {
            throw new \Exception("File extension '.{$ext}' is not allowed.");
        }

        // Verify MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, self::$allowedMimeTypes)) {
            throw new \Exception("File type '{$mimeType}' is not allowed.");
        }

        $newName = bin2hex(random_bytes(16)) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
            return 'uploads/' . $newName;
        }
        
        return null;
    }
}
