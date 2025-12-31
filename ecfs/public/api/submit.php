<?php
require_once __DIR__ . '/../../src/autoload.php';

use EazeWebIT\Submissions;
use EazeWebIT\UploadHandler;
use EazeWebIT\Security;
use EazeWebIT\Auth;
use EazeWebIT\Settings;
use EazeWebIT\Mailer;


Security::initSession();

header('Content-Type: application/json');

// Rate Limiting
$ip = Security::getClientIp() ?? 'unknown';
if (!Security::checkRateLimit($ip, 'submission', 10, 3600)) { // 10 submissions per hour per IP
    Security::logSecurityIncident('rate_limit_exceeded', "Rate limit exceeded for submissions from IP: $ip");
    echo json_encode(['status' => 'error', 'message' => 'Too many submissions. Please try again later.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// CSRF / Origin Protection
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
$isCrossOrigin = false;

if (!empty($origin)) {
    $parsedOrigin = parse_url($origin);
    $originHost = $parsedOrigin['host'] ?? '';
    if (isset($parsedOrigin['port'])) {
        $originHost .= ':' . $parsedOrigin['port'];
    }
    
    if ($originHost !== $host) {
        $isCrossOrigin = true;
    }
}

if ($isCrossOrigin) {
    // For cross-origin, validate against allowed origins
    $allowedOriginsStr = Settings::get('allowed_origins', '');
    $allowedOrigins = array_filter(array_map('trim', explode(',', $allowedOriginsStr)));
    
    $isAllowed = false;
    foreach ($allowedOrigins as $allowed) {
        if ($allowed === $origin) {
            $isAllowed = true;
            break;
        }
    }
    
    if (!$isAllowed) {
        Security::logSecurityIncident('unauthorized_origin', "Unauthorized origin: $origin");
        echo json_encode(['status' => 'error', 'message' => 'Security validation failed (Origin unauthorized)']);
        exit;
    }
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin");
} else {
    // For same-origin (or no Origin header, e.g. direct form post from same domain), use CSRF token
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Security validation failed (CSRF)']);
        exit;
    }
}

// Check if the POST data is empty but content length is present (usually indicates post_max_size exceeded)
if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    echo json_encode(['status' => 'error', 'message' => 'The total upload size exceeds the server limit (post_max_size).']);
    exit;
}

try {
    // Log the attempt for rate limiting
    Security::logSecurityIncident('submission', "Submission attempt from IP: $ip");

    // Basic validation and file processing
    $data = $_POST;
    $files = $_FILES;

    // Get the current user if authenticated
    $user = Auth::user();
    $submittedBy = $user ? $user['username'] : 'Guest';

    $uploadedFiles = UploadHandler::handle($files);
    $result = Submissions::create($data, $uploadedFiles, $submittedBy);

    if ($result['success']) {
        $submissionId = $result['submission_id'];
        
        // --- Email Notifications ---
        
        // 1. Admin Notification
        $enableAdminNotification = Settings::get('enable_admin_notification', '0') === '1';
        $adminEmail = Settings::get('admin_recipient_email');
        
        if ($enableAdminNotification && !empty($adminEmail)) {
            $subject = "New Form Submission (#$submissionId)";
            $body = "<h2>New Submission Received</h2>";
            $body .= "<p><strong>ID:</strong> $submissionId</p>";
            $body .= "<p><strong>Submitted By:</strong> $submittedBy</p>";
            $body .= "<h3>Form Data:</h3><ul>";
            foreach ($data as $key => $value) {
                if ($key === 'csrf_token') continue;
                $body .= "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "</li>";
            }
            $body .= "</ul>";

            if (!empty($uploadedFiles)) {
                $body .= "<h3>Uploaded Files:</h3><ul>";
                foreach ($uploadedFiles as $fieldName => $fileData) {
                    // Check if it's a multiple upload (array of files) or single
                    if (isset($fileData['original_name'])) {
                        // Single file
                        $body .= "<li><strong>" . htmlspecialchars($fieldName) . ":</strong> " . htmlspecialchars($fileData['original_name']) . "</li>";
                    } else if (is_array($fileData)) {
                        // Multiple files
                        foreach ($fileData as $file) {
                            if (isset($file['original_name'])) {
                                $body .= "<li><strong>" . htmlspecialchars($fieldName) . ":</strong> " . htmlspecialchars($file['original_name']) . "</li>";
                            }
                        }
                    }
                }
                $body .= "</ul>";
            }
            Mailer::send($adminEmail, $subject, $body);
        }

        // 2. User Confirmation
        $enableConfirmation = Settings::get('enable_confirmation_email', '0') === '1';
        $userEmail = '';
        foreach ($data as $key => $value) {
            if (strtolower($key) === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $userEmail = $value;
                break;
            }
        }

        if ($enableConfirmation && !empty($userEmail)) {
            $subject = "Submission Confirmation - " . Settings::get('smtp_from_name', 'EazeWebIT');
            $body = "<h2>Thank you for your submission!</h2>";
            $body .= "<p>We have received your form submission (Reference ID: #$submissionId).</p>";
            $body .= "<p>This is an automated confirmation. We will review your submission shortly.</p>";
            $body .= "<br><hr><p><small>" . Settings::get('footer_text', '') . "</small></p>";
            Mailer::send($userEmail, $subject, $body);
        }

        echo json_encode(['status' => 'success', 'message' => 'Submission successful', 'submission_id' => $submissionId]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $result['message']]);
    }
} catch (\Exception $e) {
    Security::logSecurityIncident('system_error', 'Submission exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred. Please try again later.']);
}
