<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}
?>
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Breadcrumb Header -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-800" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase">AI Security Recommendations</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Automated, prioritized hardening guidelines based on active node state</span>
        </div>
        <a href="<?= APP_URL ?>/dashboard" class="btn-secondary text-xs py-1.5 px-3">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Return to Workspace</span>
        </a>
    </div>

    <!-- Recommendations Card Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($recommendations as $rec): ?>
            <?php 
                $borderClass = 'border-slate-800';
                $priorityBg = 'bg-slate-900/40';
                $priorityColor = 'text-slate-400';
                
                if ($rec['priority'] === 'critical') {
                    $borderClass = 'border-rose-500/30';
                    $priorityBg = 'bg-rose-500/5';
                    $priorityColor = 'text-rose-500';
                } elseif ($rec['priority'] === 'high') {
                    $borderClass = 'border-red-400/30';
                    $priorityBg = 'bg-red-400/5';
                    $priorityColor = 'text-red-400';
                } elseif ($rec['priority'] === 'medium') {
                    $borderClass = 'border-amber-500/30';
                    $priorityBg = 'bg-amber-500/5';
                    $priorityColor = 'text-amber-500';
                } elseif ($rec['priority'] === 'low') {
                    $borderClass = 'border-cyan-500/20';
                    $priorityBg = 'bg-cyan-500/5';
                    $priorityColor = 'text-cyan-400';
                }
            ?>
            <div class="cyber-card flex flex-col justify-between p-6 border rounded-2xl <?= $borderClass ?> <?= $priorityBg ?> transition-all duration-200 hover:scale-[1.01]">
                <div class="space-y-4">
                    <!-- Card Top: Priority Badge & Icon -->
                    <div class="flex items-center justify-between">
                        <span class="text-3xs uppercase font-mono font-bold tracking-widest px-2.5 py-0.5 rounded border border-current <?= $priorityColor ?>">
                            <?= htmlspecialchars($rec['priority']) ?> PRIORITY
                        </span>
                        <div class="p-2 rounded-lg" style="background-color: rgba(255,255,255,0.02); border: 1px solid var(--color-border);">
                            <i data-lucide="<?= htmlspecialchars($rec['icon']) ?>" class="w-5 h-5 <?= $priorityColor ?>"></i>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-white font-mono uppercase tracking-wide">
                            <?= htmlspecialchars($rec['title']) ?>
                        </h3>
                        <p class="text-xs leading-relaxed text-gray-300">
                            <?= htmlspecialchars($rec['description']) ?>
                        </p>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="mt-6 pt-4 border-t" style="border-color: var(--color-border);">
                    <a href="<?= htmlspecialchars($rec['action_url']) ?>" class="btn-primary w-full justify-center text-xs py-2">
                        <span><?= htmlspecialchars($rec['action_lbl']) ?></span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
