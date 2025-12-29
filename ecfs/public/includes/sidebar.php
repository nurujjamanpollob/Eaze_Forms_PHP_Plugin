<?php
$current_page = basename($_SERVER['PHP_SELF']);
$isAdmin = \\EazeWebIT\\Auth::isAdmin();
?>
<aside class=\"w-64 bg-slate-900 h-screen sticky top-0 flex flex-col border-r border-white/10\">
    <div class=\"p-6\">
        <div class=\"flex items-center space-x-3 mb-10\">
            <img src=\"/ecfs/public/assets/logo.png\" alt=\"Logo\" class=\"h-8\">
            <a href=\"https://eazewebit.com\" target=\"_blank\" class=\"text-lg font-bold text-sky-400 hover:text-sky-300 transition\">Eaze Web IT</a>
        </div>

        <nav class=\"space-y-1\">
            <a href=\"dashboard.php\" class=\"flex items-center space-x-3 px-4 py-3 rounded-xl transition <?= $current_page == 'dashboard.php' ? 'bg-sky-500/10 text-sky-400' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>\">
                <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z\"></path></svg>
                <span>Submissions</span>
            </a>
            
            <a href=\"settings.php\" class=\"flex items-center space-x-3 px-4 py-3 rounded-xl transition <?= $current_page == 'settings.php' ? 'bg-sky-500/10 text-sky-400' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>\">
                <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z\"></path><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 12a3 3 0 11-6 0 3 3 0 016 0z\"></path></svg>
                <span>Settings</span>
            </a>

            <?php if ($isAdmin): ?>
                <div class=\"pt-4 pb-2\">
                    <p class=\"px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest\">Admin Tools</p>
                </div>

                <a href=\"manage_submissions.php\" class=\"flex items-center space-x-3 px-4 py-3 rounded-xl transition <?= $current_page == 'manage_submissions.php' ? 'bg-sky-500/10 text-sky-400' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>\">
                    <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01\"></path></svg>
                    <span>Bulk Manage</span>
                </a>

                <a href=\"admin_users.php\" class=\"flex items-center space-x-3 px-4 py-3 rounded-xl transition <?= $current_page == 'admin_users.php' ? 'bg-sky-500/10 text-sky-400' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>\">
                    <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z\"></path></svg>
                    <span>Manage Users</span>
                </a>

                <a href=\"admin_roles.php\" class=\"flex items-center space-x-3 px-4 py-3 rounded-xl transition <?= $current_page == 'admin_roles.php' ? 'bg-sky-500/10 text-sky-400' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>\">
                    <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.040L3 14.535a12.02 12.02 0 0010.182 7.447 12.02 12.02 0 0010.182-7.447l-1.382-8.511z\"></path></svg>
                    <span>Manage Roles</span>
                </a>

                <a href=\"admin_statuses.php\" class=\"flex items-center space-x-3 px-4 py-3 rounded-xl transition <?= $current_page == 'admin_statuses.php' ? 'bg-sky-500/10 text-sky-400' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>\">
                    <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01\"></path></svg>
                    <span>Status Labels</span>
                </a>

                <a href=\"admin_logs.php\" class=\"flex items-center space-x-3 px-4 py-3 rounded-xl transition <?= $current_page == 'admin_logs.php' ? 'bg-sky-500/10 text-sky-400' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>\">
                    <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"></path></svg>
                    <span>System Logs</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class=\"mt-auto p-6 border-t border-white/10\">
        <a href=\"logout.php\" class=\"flex items-center space-x-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition\">
            <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1\"></path></svg>
            <span>Logout</span>
        </a>
    </div>
</aside>