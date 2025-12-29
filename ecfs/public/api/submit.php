<?php
require_once __DIR__ . '/../../src/autoload.php';

use EazeWebIT\Submissions;
use EazeWebIT\UploadHandler;
use EazeWebIT\Security;
use EazeWebIT\Auth;

Security::initSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// CSRF Protection
if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Security validation failed (CSRF)']);
    exit;
}

// Check if the POST data is empty but content length is present (usually indicates post_max_size exceeded)
if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    echo json_encode(['status' => 'error', 'message' => 'The total upload size exceeds the server limit (post_max_size).']);
    exit;
}

try {
    // Basic validation and file processing
    $data = $_POST;
    $files = $_FILES;

    // Get the current user if authenticated
    $user = Auth::user();
    $submittedBy = $user ? $user['username'] : 'Guest';

    $uploadedFiles = UploadHandler::handle($files);
    $result = Submissions::create($data, $uploadedFiles, $submittedBy);

    if ($result['success']) {
        echo json_encode(['status' => 'success', 'message' => 'Submission successful', 'submission_id' => $result['submission_id']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $result['message']]);
    }
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
