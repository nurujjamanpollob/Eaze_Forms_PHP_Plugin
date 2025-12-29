<?php
require_once __DIR__ . '/../src/autoload.php';
use EazeWebIT\Auth;

/**
 * File Download Handler
 * Restricts access to authenticated users and prevents directory traversal.
 */

// 1. Authenticated Access Check
if (!Auth::check()) {
    header('HTTP/1.1 403 Forbidden');
    die("Access denied: You must be logged in to access attachments.");
}

$file = $_GET['file'] ?? '';
$inline = isset($_GET['inline']);

if (empty($file)) {
    header('HTTP/1.1 400 Bad Request');
    die("No file specified");
}

// 2. Security: Prevent directory traversal and access to hidden files
$filename = basename($file);

// Prevent access to hidden files (like .htaccess)
if (strpos($filename, '.') === 0) {
    header('HTTP/1.1 403 Forbidden');
    die("Access denied");
}

$uploadDir = realpath(__DIR__ . '/../uploads/');
$filePath = realpath($uploadDir . DIRECTORY_SEPARATOR . $filename);

// 3. Verify file existence and location
if ($filePath && strpos($filePath, $uploadDir) === 0 && file_exists($filePath)) {
    
    // Optional: Could verify if file is registered in submissions table here
    // for even tighter security.
    
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
    die("File not found or access denied.");
}
