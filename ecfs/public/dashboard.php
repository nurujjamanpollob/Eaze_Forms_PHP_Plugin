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

$filters = [
    'status' => $_GET['status'] ?? 'All Statuses',
    'search' => $_GET['search'] ?? ''
];

$submissions = Submissions::getAll($filters);
$isAdmin = Auth::isAdmin();
$availableStatuses = Statuses::all();
$colorMap = Statuses::getColorMap();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EazeWebIT</title>
    <script src="https://cdn.tailwindcss.com" nonce="<?= $nonce ?>"></script>
    <style>
        body { background-color: #0f172a; }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .card-hover:hover { transform: translateY(-4px); transition: all 0.3s ease; }
        select option { background: #1e293b; color: white; }
    </style>
</head>
<body class="text-gray-200 min-h-screen flex">
    
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-bold">Recent Submissions</h2>
                <p class="text-gray-500">Manage and track your incoming form data</p>
            </div>
            <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4 w-full md:w-auto">
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
                    <button type="submit" class="hidden">Search</button>
                </form>
                <?php if ($isAdmin): ?>
                <a href="manage_submissions.php" class="bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded-lg font-bold text-center">
                    Manage All
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div id="submissions-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($submissions as $sub): 
                $currentStatus = $sub['status'] ?? 'pending';
                $statusClasses = Statuses::getTailwindClasses($currentStatus, $colorMap);
            ?>
            <div class="glass p-6 rounded-2xl card-hover border-t-4 border-sky-500/30">
                <div class="flex justify-between items-start mb-4">
                    <span class="text-xs text-gray-400"><?= $sub['created_at'] ?></span>
                    <span class="px-2 py-1 text-[10px] font-bold rounded bg-sky-500/20 text-sky-400 uppercase tracking-widest">ID: <?= $sub['submission_id'] ?></span>
                </div>
                <div class="space-y-3">
                    <?php 
                    $displayFields = 0;
                    foreach ($sub as $key => $val): 
                        if (in_array($key, ['submission_id', 'created_at', 'status'])) continue;
                        if ($displayFields >= 4) break;
                        $displayFields++;
                    ?>
                        <div>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-tighter"><?= htmlspecialchars($key) ?></span>
                            <p class="text-sm truncate font-medium"><?= htmlspecialchars(is_array($val) ? json_encode($val) : $val) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-6 pt-4 border-t border-white/10 flex justify-between items-center">
                    <span class="text-xs font-bold uppercase px-2 py-0.5 rounded border <?= $statusClasses ?>">
                        <?= htmlspecialchars($currentStatus) ?>
                    </span>
                    <a href="submission_preview.php?id=<?= $sub['submission_id'] ?>" class="text-sky-400 hover:text-sky-300 text-sm font-bold flex items-center">
                        View Details <span class="ml-1">→</span>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($submissions)): ?>
            <div class="col-span-full text-center py-20 glass rounded-3xl">
                <div class="text-5xl mb-4">📥</div>
                <p class="text-gray-500 italic">No submissions found yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/copyright.php'; ?>

    <script nonce="<?= $nonce ?>">
        document.addEventListener('DOMContentLoaded', function() {
            // Handle filter change
            const statusFilter = document.getElementById('status-filter');
            if (statusFilter) {
                statusFilter.addEventListener('change', function() {
                    document.getElementById('filter-form').submit();
                });
            }

            // Auto-refresh every 30 seconds if no search is active
            const hasSearch = <?= !empty($filters['search']) ? 'true' : 'false' ?>;
            const hasStatusFilter = <?= $filters['status'] !== 'All Statuses' ? 'true' : 'false' ?>;
            
            if (!hasSearch && !hasStatusFilter) {
                setInterval(() => {
                    window.location.reload();
                }, 30000);
            }
        });
    </script>
</body>
</html>