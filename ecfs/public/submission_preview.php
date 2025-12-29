<?php
require_once __DIR__ . '/../src/autoload.php';
use EazeWebIT\Submissions;
use EazeWebIT\Auth;
use EazeWebIT\Statuses;
use EazeWebIT\Security;

if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

Security::initSession();
$nonce = Security::getNonce();

$id = $_GET['id'] ?? null;

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } else {
        $newStatus = $_POST['status'] ?? '';
        if ($newStatus && Statuses::exists($newStatus)) {
            Submissions::updateStatus($id, $newStatus, $_SESSION['user_id']);
            $message = "Status updated to " . htmlspecialchars($newStatus);
        }
    }
}

$submission = Submissions::getById($id);
if (!$submission) {
    die("Submission not found");
}

$availableStatuses = Statuses::all();
$colorMap = Statuses::getColorMap();
$submittedBy = $submission['submitted_by'] ?? 'Guest';
$csrfToken = Security::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <title>Preview Submission - EazeWebIT</title>
    <script src="https://cdn.tailwindcss.com" nonce="<?= $nonce ?>"></script>
    <style>
        body { background: #0f172a; color: white; }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-gray-200 min-h-screen flex">
    
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <div class="max-w-4xl">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <a href="manage_submissions.php" class="text-sky-400 hover:underline text-sm mb-2 inline-block">← Back to Submissions</a>
                    <h1 class="text-3xl font-bold">Submission #<?= htmlspecialchars($id) ?></h1>
                    <p class="text-gray-500">Received on <?= htmlspecialchars($submission['created_at']) ?></p>
                </div>
                
                <form method="POST" class="flex items-center space-x-2 bg-white/5 p-2 rounded-xl border border-white/10">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <span class="text-xs font-bold text-gray-500 uppercase ml-2">Status:</span>
                    <select name="status" class="bg-white/10 border border-white/20 rounded-lg px-3 py-1 text-sm focus:ring-2 focus:ring-sky-500 text-white outline-none">
                        <?php foreach ($availableStatuses as $st): ?>
                            <option value="<?= htmlspecialchars($st['status']) ?>" <?= ($submission['status'] ?? 'pending') === $st['status'] ? 'selected' : '' ?> class="bg-slate-800">
                                <?= ucfirst(htmlspecialchars($st['status'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="update_status" class="bg-sky-600 hover:bg-sky-500 px-4 py-1.5 rounded-lg text-xs font-bold transition">Update</button>
                </form>
            </div>

            <?php if (isset($message)): ?>
                <div class="bg-green-500/20 text-green-400 p-4 rounded-lg mb-6 border border-green-500/30"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-red-500/20 text-red-400 p-4 rounded-lg mb-6 border border-red-500/30"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="glass p-8 rounded-3xl shadow-2xl">
                <div class="space-y-8">
                    <div class="flex justify-between items-center border-b border-white/10 pb-6">
                        <div class="flex flex-col">
                            <h3 class="text-xs font-bold text-sky-500 uppercase tracking-wider">Submitted By</h3>
                            <div class="flex items-center space-x-2 mt-1">
                                <div class="w-8 h-8 rounded-full bg-sky-500/20 flex items-center justify-center text-sky-400 font-bold">
                                    <?= strtoupper(substr($submittedBy, 0, 1)) ?>
                                </div>
                                <span class="text-lg <?= $submittedBy === 'Guest' ? 'text-gray-500 italic' : 'text-white font-bold' ?>">
                                    <?= htmlspecialchars($submittedBy) ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <h3 class="text-xs font-bold text-sky-500 uppercase tracking-wider">Current Status</h3>
                            <span class="inline-block mt-1 px-3 py-1 text-xs font-bold rounded-full border <?= Statuses::getTailwindClasses($submission['status'] ?? 'pending', $colorMap) ?>">
                                <?= strtoupper(htmlspecialchars($submission['status'] ?? 'pending')) ?>
                            </span>
                        </div>
                    </div>

                    <?php foreach ($submission['fields'] as $field): 
                        $key = $field['field_key'];
                        $val = $field['field_value'];
                        $type = $field['field_type'];
                        if ($key === 'status' || $key === 'submitted_by') continue; // Handled separately
                    ?>
                    <div class="border-b border-white/10 pb-6 last:border-0">
                        <h3 class="text-xs font-bold text-sky-500 uppercase tracking-wider mb-2"><?= htmlspecialchars($key) ?></h3>
                        
                        <?php if ($type === 'file'): 
                            $files = json_decode($val, true);
                            if (!is_array($files)) $files = [$val];
                            foreach ($files as $f):
                                $filename = basename($f);
                                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                $fileUrl = "download.php?file=" . urlencode($filename);
                                $viewUrl = $fileUrl . "&inline=1";
                        ?>
                            <div class="mt-2">
                                <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img src="<?= $viewUrl ?>" class="max-w-xs rounded-lg border border-white/20 shadow-lg" loading="lazy">
                                    <div class="mt-2">
                                        <a href="<?= $fileUrl ?>" class="text-xs text-sky-400 hover:underline">Download Original</a>
                                    </div>
                                <?php elseif (in_array($ext, ['mp4', 'webm'])): ?>
                                    <video controls class="max-w-md rounded-lg border border-white/20">
                                        <source src="<?= $viewUrl ?>">
                                    </video>
                                    <div class="mt-2">
                                        <a href="<?= $fileUrl ?>" class="text-xs text-sky-400 hover:underline">Download Video</a>
                                    </div>
                                <?php else: ?>
                                    <div class="flex items-center space-x-3 p-3 bg-white/5 rounded-lg border border-white/10">
                                        <span class="text-2xl">📄</span>
                                        <span class="flex-1 truncate"><?= htmlspecialchars($filename) ?></span>
                                        <a href="<?= $fileUrl ?>" class="text-sky-400 hover:text-sky-300 font-bold">Download</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <?php elseif (strpos($key, 'phone') !== false): ?>
                            <div class="flex items-center space-x-2 text-lg">
                                <span>📞</span>
                                <span class="font-mono"><?= htmlspecialchars($val) ?></span>
                                <button data-copy="<?= htmlspecialchars($val) ?>" class="copy-btn text-xs bg-white/10 px-2 py-1 rounded hover:bg-white/20">Copy</button>
                            </div>

                        <?php elseif (strpos($key, 'email') !== false): ?>
                            <div class="flex items-center space-x-2 text-lg">
                                <span>✉️</span>
                                <a href="mailto:<?= htmlspecialchars($val) ?>" class="text-sky-400 hover:underline"><?= htmlspecialchars($val) ?></a>
                            </div>

                        <?php elseif (filter_var($val, FILTER_VALIDATE_URL)): ?>
                            <a href="<?= htmlspecialchars($val) ?>" target="_blank" rel="noopener noreferrer" class="block p-4 bg-white/5 rounded-xl border border-white/10 hover:bg-white/10 transition">
                                <div class="flex items-center justify-between">
                                    <span class="truncate text-sky-300"><?= htmlspecialchars($val) ?></span>
                                    <span class="text-xs">↗</span>
                                </div>
                            </a>

                        <?php else: ?>
                            <div class="relative group">
                                <pre class="bg-black/20 p-4 rounded-xl text-sm whitespace-pre-wrap font-sans"><?php 
                                    // Fix for literal \n strings showing up
                                    $displayVal = str_replace('\n', "\n", $val);
                                    echo htmlspecialchars($displayVal); 
                                ?></pre>
                                <button class="copy-pre-btn absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition bg-sky-600 text-xs px-2 py-1 rounded">Copy</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/copyright.php'; ?>

    <script nonce="<?= $nonce ?>">
        document.addEventListener('DOMContentLoaded', function() {
            // Copy buttons for phone/text
            document.querySelectorAll('.copy-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const text = this.getAttribute('data-copy');
                    navigator.clipboard.writeText(text).then(() => {
                        const originalText = this.textContent;
                        this.textContent = 'Copied!';
                        setTimeout(() => this.textContent = originalText, 2000);
                    });
                });
            });

            // Copy buttons for pre blocks
            document.querySelectorAll('.copy-pre-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const pre = this.previousElementSibling;
                    navigator.clipboard.writeText(pre.innerText).then(() => {
                        const originalText = this.textContent;
                        this.textContent = 'Copied!';
                        setTimeout(() => this.textContent = originalText, 2000);
                    });
                });
            });
        });
    </script>
</body>
</html>
