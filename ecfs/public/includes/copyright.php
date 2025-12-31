<?php
$footer_text = '© ' . date('Y') . ' <a href="https://eazewebit.com" target="_blank" class="hover:text-sky-400">Eaze Web IT</a>. All rights reserved.';
if (class_exists('EazeWebIT\Database')) {
    try {
        $db_instance = \EazeWebIT\Database::getInstance();
        $stmt = $db_instance->query("SELECT Value FROM settings WHERE Key = 'footer_text'");
        $db_val = $stmt->fetchColumn();
        if ($db_val) $footer_text = $db_val;
    } catch (Exception $e) {
        // Fallback to default
    }
}
?>
<style>
    .copyright-glass {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    }
</style>
<footer class="fixed bottom-4 left-0 w-full flex justify-center pointer-events-none z-[9999]">
    <div class="copyright-glass px-5 py-1.5 rounded-full text-[10px] uppercase tracking-widest font-bold text-gray-500/60 pointer-events-auto transition-all hover:text-sky-400/80 hover:border-sky-500/30">
        <?php echo $footer_text; // Note: Intentionally allowing HTML for the link ?>
    </div>
</footer>