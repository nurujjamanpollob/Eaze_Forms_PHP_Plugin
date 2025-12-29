<?php
require_once __DIR__ . '/../src/autoload.php';
use EazeWebIT\Submissions;
use EazeWebIT\Auth;
use EazeWebIT\Statuses;
use EazeWebIT\Security;

if (!Auth::check() || !Auth::isAdmin()) {
    header('Location: login.php');
    exit;
}

Security::initSession();
$nonce = Security::getNonce();

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } else {
        $action = $_POST['action'] ?? '';
        $ids = $_POST['ids'] ?? [];

        if (!empty($ids)) {
            if ($action === 'bulk_delete') {
                Submissions::bulkDelete($ids);
                $message = "Selected submissions deleted successfully.";
            } elseif (strpos($action, 'status_') === 0) {
                $newStatus = str_replace('status_', '', $action);
                if (Statuses::exists($newStatus)) {
                    Submissions::bulkUpdateStatus($ids, $newStatus, Auth::user()['id']);
                    $message = "Status updated to " . htmlspecialchars($newStatus) . " for selected submissions.";
                }
            }
        }
    }
}

$filters = [
    'status' => $_GET['status'] ?? 'All Statuses',
    'search' => $_GET['search'] ?? ''
];

$submissions = Submissions::getAll($filters);
$availableStatuses = Statuses::all();
$colorMap = Statuses::getColorMap();
$csrfToken = Security::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Submissions - EazeWebIT</title>
    <script src="https://cdn.tailwindcss.com" nonce="<?= $nonce ?>"></script>
    <style>
        body { background-color: #0f172a; }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
        select option { background: #1e293b; color: white; }
    </style>
</head>
<body class="text-gray-200 min-h-screen flex">
    
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-bold">Manage Submissions</h2>
                <p class="text-gray-500">Bulk actions and detailed management</p>
            </div>
            <div class="flex space-x-4">
                <a href="dashboard.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg font-bold">
                    Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="bg-green-500/20 border border-green-500 text-green-500 p-4 rounded-lg mb-6">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-500 p-4 rounded-lg mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="glass rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-white/10 flex flex-col md:flex-row justify-between items-center gap-4">
                <form id="filter-form" method="GET" class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4 w-full md:w-auto">
                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Search..." class="bg-white/10 border border-white/20 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500 flex-1">
                    <select name="status" id="status-filter" class="bg-white/10 border border-white/20 rounded-lg px-4 py-2 focus:outline-none">
                        <option <?= $filters['status'] == 'All Statuses' ? 'selected' : '' ?>>All Statuses</option>
                        <?php foreach ($availableStatuses as $st): ?>
                            <option value="<?= htmlspecialchars($st['status']) ?>" <?= $filters['status'] == $st['status'] ? 'selected' : '' ?>>
                                <?= ucfirst(htmlspecialchars($st['status'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <div class="flex flex-wrap gap-2 justify-center md:justify-end">
                    <?php foreach ($availableStatuses as $st): ?>
                        <button type="button" data-action="status_<?= htmlspecialchars($st['status']) ?>" 
                                class="bulk-action-btn px-3 py-1 rounded text-[10px] font-bold uppercase transition border <?= Statuses::getTailwindClasses($st['status'], $colorMap) ?> hover:opacity-80">
                            Mark <?= htmlspecialchars($st['status']) ?>
                        </button>
                    <?php endforeach; ?>
                    <button type="button" data-action="bulk_delete" class="bulk-action-btn bg-red-600/20 text-red-500 border border-red-500/50 px-3 py-1 rounded text-[10px] font-bold uppercase hover:bg-red-600/30">Delete Selected</button>
                </div>
            </div>

            <form id="bulk-form" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="action" id="bulk-action">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-white/5 text-[10px] uppercase tracking-widest text-gray-500">
                            <th class="p-4 w-10">
                                <input type="checkbox" id="select-all" class="rounded bg-white/10 border-white/20">
                            </th>
                            <th class="p-4">Submission ID</th>
                            <th class="p-4">Submitted By</th>
                            <th class="p-4">Date</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($submissions as $sub): 
                            $currentStatus = $sub['status'] ?? 'pending';
                            $submittedBy = $sub['submitted_by'] ?? 'Guest';
                        ?>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="p-4">
                                <input type="checkbox" name="ids[]" value="<?= htmlspecialchars($sub['submission_id']) ?>" class="sub-checkbox rounded bg-white/10 border-white/20">
                            </td>
                            <td class="p-4">
                                <div class="font-mono text-sky-400">#<?= htmlspecialchars($sub['submission_id']) ?></div>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center text-[10px] font-bold">
                                        <?= strtoupper(substr($submittedBy, 0, 1)) ?>
                                    </div>
                                    <span class="text-sm <?= $submittedBy === 'Guest' ? 'text-gray-500 italic' : 'text-gray-200' ?>">
                                        <?= htmlspecialchars($submittedBy) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 text-sm text-gray-400">
                                <?= htmlspecialchars($sub['created_at']) ?>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-[10px] font-bold rounded uppercase border <?= Statuses::getTailwindClasses($currentStatus, $colorMap) ?>">
                                    <?= htmlspecialchars($currentStatus) ?>
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <a href="submission_preview.php?id=<?= htmlspecialchars($sub['submission_id']) ?>" class="text-sky-400 hover:text-sky-300 text-sm font-bold">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="6" class="p-10 text-center text-gray-500 italic">No submissions found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
    </main>

    <?php include 'includes/copyright.php'; ?>

    <script nonce="<?= $nonce ?>">
        document.addEventListener('DOMContentLoaded', function() {
            // Select All
            const selectAll = document.getElementById('select-all');
            if (selectAll) {
                selectAll.addEventListener('click', function() {
                    const checkboxes = document.getElementsByClassName('sub-checkbox');
                    for (let checkbox of checkboxes) {
                        checkbox.checked = this.checked;
                    }
                });
            }

            // Status Filter
            const statusFilter = document.getElementById('status-filter');
            if (statusFilter) {
                statusFilter.addEventListener('change', function() {
                    this.form.submit();
                });
            }

            // Bulk Actions
            const bulkButtons = document.querySelectorAll('.bulk-action-btn');
            bulkButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.getAttribute('data-action');
                    submitBulkAction(action);
                });
            });

            function submitBulkAction(action) {
                const checkboxes = document.querySelectorAll('.sub-checkbox:checked');
                if (checkboxes.length === 0) {
                    alert('Please select at least one submission.');
                    return;
                }

                if (action === 'bulk_delete' && !confirm('Are you sure you want to delete selected submissions? This action cannot be undone.')) {
                    return;
                }

                document.getElementById('bulk-action').value = action;
                document.getElementById('bulk-form').submit();
            }
        });
    </script>
</body>
</html>