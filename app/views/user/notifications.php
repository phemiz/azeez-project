<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$csrfToken = \App\Core\Session::generateCSRFToken();
?>
<div class="space-y-6 max-w-4xl mx-auto animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-800" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase font-bold">Notifications History</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Chronological registry of cellular alerts, security alarms, and status triggers</span>
        </div>
        <div class="flex gap-2">
            <button onclick="readAllNotifications()" class="btn-primary text-xs py-1.5 px-4 cursor-pointer">
                <i data-lucide="check-check" class="w-4 h-4"></i>
                <span>Mark All Read</span>
            </button>
            <a href="<?= APP_URL ?>/dashboard" class="btn-secondary text-xs py-1.5 px-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="space-y-4">
        <?php if (empty($notifications)): ?>
            <div class="cyber-card p-8 text-center text-xs font-mono" style="color: var(--color-foreground-muted);">
                Zero notifications registered for your operator node. System healthy.
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <?php 
                    $borderClass = 'border-slate-800';
                    $bgClass = 'bg-slate-900/40';
                    $colorClass = 'text-slate-400';
                    $icon = 'bell';

                    if ($n['type'] === 'success') {
                        $borderClass = 'border-emerald-500/20';
                        $bgClass = 'bg-emerald-500/5';
                        $colorClass = 'text-emerald-400';
                        $icon = 'shield-check';
                    } elseif ($n['type'] === 'warning') {
                        $borderClass = 'border-amber-500/20';
                        $bgClass = 'bg-amber-500/5';
                        $colorClass = 'text-amber-500';
                        $icon = 'alert-triangle';
                    } elseif ($n['type'] === 'error') {
                        $borderClass = 'border-red-500/20';
                        $bgClass = 'bg-red-500/5';
                        $colorClass = 'text-red-400';
                        $icon = 'shield-x';
                    } elseif ($n['type'] === 'security') {
                        $borderClass = 'border-rose-500/30';
                        $bgClass = 'bg-rose-500/5';
                        $colorClass = 'text-rose-500 font-bold';
                        $icon = 'shield-alert';
                    }
                ?>
                <div id="notif-row-<?= $n['id'] ?>" 
                     class="cyber-card p-5 border rounded-2xl flex items-start justify-between gap-6 transition-all duration-200 <?= $n['is_read'] ? 'opacity-50' : 'hover:scale-[1.005]' ?> <?= $borderClass ?> <?= $bgClass ?>">
                    
                    <div class="flex items-start gap-4">
                        <div class="p-2.5 rounded-lg border" style="background-color: rgba(255,255,255,0.02); border-color: var(--color-border);">
                            <i data-lucide="<?= $icon ?>" class="w-5 h-5 <?= $colorClass ?>"></i>
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wide flex items-center gap-2">
                                <span><?= htmlspecialchars($n['title']) ?></span>
                                <?php if (!$n['is_read']): ?>
                                    <span class="w-2 h-2 rounded-full bg-cyan-500 animate-ping"></span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-2xs leading-relaxed text-gray-300">
                                <?= htmlspecialchars($n['message']) ?>
                            </p>
                            <span class="text-3xs block pt-1 font-mono" style="color: var(--color-foreground-muted);">
                                <?= date('Y-m-d H:i:s', strtotime($n['created_at'])) ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!$n['is_read']): ?>
                        <button onclick="readSingleNotification(<?= $n['id'] ?>)" 
                                class="py-1 px-3 border border-slate-700 hover:bg-slate-800 text-gray-300 rounded text-3xs font-bold cursor-pointer font-mono print:hidden">
                            Dismiss
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Read Single Notification
async function readSingleNotification(id) {
    try {
        const formData = new FormData();
        formData.append('notification_id', id);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/api/notifications/read', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            const row = document.getElementById('notif-row-' + id);
            row.classList.add('opacity-50');
            row.classList.remove('hover:scale-[1.005]');
            
            // Remove dismiss button
            const btn = row.querySelector('button');
            if (btn) btn.remove();

            // Remove ping badge
            const ping = row.querySelector('.animate-ping');
            if (ping) ping.remove();
        } else {
            alert('Failed to dismiss alert.');
        }
    } catch (e) {
        alert('Network execution failure.');
    }
}

// Read All Notifications
async function readAllNotifications() {
    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/api/notifications/read-all', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            window.location.reload();
        } else {
            alert('Failed to mark all as read.');
        }
    } catch (e) {
        alert('Network execution failure.');
    }
}
</script>
