<?php
/**
 * Automated Test Script for EazeWebIT Contact Form Service
 * Run via CLI: php tests/test_submission.php
 */

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Submissions.php';

use EazeWebIT\Submissions;
use EazeWebIT\Database;

echo "--- Starting Core Functional Tests ---\n";

// 1. Test Database Connection
try {
    $db = Database::getInstance();
    echo "[PASS] Database connection successful.\n";
} catch (Exception $e) {
    die("[FAIL] Database connection failed: " . $e->getMessage() . "\n");
}

// 2. Test Submission Creation (EAV Logic)
$testData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'message' => 'Hello, this is a test message.',
    'phone' => '+1 (555) 000-1111'
];

$result = Submissions::create($testData);

if ($result['success']) {
    echo "[PASS] Submission created successfully. ID: " . $result['submission_id'] . "\n";
    $sid = $result['submission_id'];
} else {
    die("[FAIL] Submission creation failed: " . $result['message'] . "\n");
}

// 3. Verify Data in Database
$stmt = $db->prepare("SELECT COUNT(*) FROM submissions WHERE submission_id = ?");
$stmt->execute([$sid]);
$count = $stmt->fetchColumn();

if ($count == count($testData)) {
    echo "[PASS] All EAV fields persisted correctly (Count: $count).\n";
} else {
    echo "[FAIL] Data persistence mismatch. Expected " . count($testData) . " fields, found $count.\n";
}

// 4. Test Retrieval
$submissions = Submissions::getAll();
$found = false;
foreach ($submissions as $s) {
    if ($s['submission_id'] == $sid) {
        $found = true;
        break;
    }
}

if ($found) {
    echo "[PASS] Submission retrieved from dashboard list.\n";
} else {
    echo "[FAIL] Submission not found in retrieval list.\n";
}

echo "--- Tests Completed Successfully ---\n";
