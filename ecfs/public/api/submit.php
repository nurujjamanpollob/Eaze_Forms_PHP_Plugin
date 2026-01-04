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
        
        // Prepare template data
        $templateData = array_merge($data, [
            'submission_id' => $submissionId,
            'submitted_by' => $submittedBy,
            'footer_text' => Settings::get('footer_text', '')
        ]);

        // --- Email Notifications ---
        
        // 1. Admin Notification
        $enableAdminNotification = Settings::get('enable_admin_notification', '0') === '1';
        $adminEmail = Settings::get('admin_recipient_email');
        
        if ($enableAdminNotification && !empty($adminEmail)) {
            $subject = "New Form Submission (#$submissionId)";
            $adminTemplate = Settings::get('admin_email_template', '<h2>New Submission Received</h2><p><strong>ID:</strong> {{submission_id}}</p><p><strong>Submitted By:</strong> {{submitted_by}}</p><h3>Form Data:</h3>{{form_data}}');
            $body = Mailer::parseTemplate($adminTemplate, $templateData);
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
            $userTemplate = Settings::get('user_email_template', '<h2>Thank you for your submission!</h2><p>We have received your form submission (Reference ID: #{{submission_id}}).</p><p>This is an automated confirmation. We will review your submission shortly.</p><br><hr><p><small>{{footer_text}}</small></p>');
            $body = Mailer::parseTemplate($userTemplate, $templateData);
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
