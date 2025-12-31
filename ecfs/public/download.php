<?php
/**
 * File Download Handler
 * Restricts access to authenticated users and prevents directory traversal.
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../src/autoload.php';

use EazeWebIT\Auth;

try {
    // 1. Authenticated Access Check
    if (!Auth::check()) {
        header('HTTP/1.1 403 Forbidden');
        throw new Exception("Access denied: You must be logged in to access attachments.");
    }

    $file = $_GET['file'] ?? '';
    $inline = isset($_GET['inline']);

    if (empty($file)) {
        header('HTTP/1.1 400 Bad Request');
        throw new Exception("No file specified");
    }

    // 2. Security: Prevent directory traversal and access to hidden files
    $filename = basename($file);

    // Prevent access to hidden files (like .htaccess)
    if (strpos($filename, '.') === 0) {
        header('HTTP/1.1 403 Forbidden');
        throw new Exception("Access denied");
    }

    // if user not admin, cannot download files
    if (!Auth::isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        throw new Exception("Access denied: You do not have permission to download this file.");
    }

    $uploadDir = realpath(__DIR__ . '/../uploads/');
    $filePath = realpath($uploadDir . DIRECTORY_SEPARATOR . $filename);

    // 3. Verify file existence and location
    if ($filePath && strpos($filePath, $uploadDir) === 0 && file_exists($filePath)) {
        
        $mimeType = mime_content_type($filePath);
        
        // Set headers for secure file delivery
        header('Content-Type: ' . $mimeType);
        $disposition = $inline ? 'inline' : 'attachment';
        header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        
        // Cache control for private data
        header('Cache-Control: private, max-age=3600');
        header('Pragma: no-cache');
        
        // Clear output buffer to prevent corruption
        if (ob_get_level()) ob_end_clean();
        
        readfile($filePath);
        exit;
    } else {
        header('HTTP/1.1 404 Not Found');
        throw new Exception("File not found or access denied.");
    }
} catch (Exception $e) {
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo "<!DOCTYPE html><html><head><title>Error</title><style>body{font-family:sans-serif;padding:2rem;background:#0f172a;color:#f87171;}</style></head><body>";
    echo "<h1>Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<a href='dashboard.php' style='color:#38bdf8;'>Back to Dashboard</a>";
    echo "</body></html>";
    exit;
}
