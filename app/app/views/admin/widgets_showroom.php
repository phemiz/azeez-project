<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

use App\Core\Widget;
?>
<div class="space-y-8 animate-fade-in">
    <!-- Header Block -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-4 border-b border-slate-800 gap-4" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase">Reusable Widgets Showroom</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Stateful UI components mapping for statistics, actions, logs, alerts, tables, and graphs</span>
        </div>
        <a href="<?= APP_URL ?>/admin" class="btn-secondary text-xs py-1.5 px-3">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Return to Terminal</span>
        </a>
    </div>

    <!-- 1. Statistics Cards Showcase -->
    <div class="space-y-4">
        <h3 class="text-xs font-bold text-white uppercase font-mono tracking-widest">1. Statistics Cards Portfolio</h3>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-6">
            <?= Widget::statCard('GSM Encryptions', '1,240', 'mail', 'cyan', 'AES-256 Envelopes') ?>
            <?= Widget::statCard('Security score', '94/100', 'shield-check', 'emerald', 'Nominal Node Health') ?>
            <?= Widget::statCard('Active Alarms', '3', 'shield-alert', 'rose', 'Requires Mitigation') ?>
            <?= Widget::statCard('Backup Snapshots', '12', 'database-backup', 'amber', 'All Dumps Verified') ?>
        </div>
    </div>

    <!-- 2. Risk Matrix & Threat Intel Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="space-y-4 md:col-span-1">
            <h3 class="text-xs font-bold text-white uppercase font-mono tracking-widest">2. AI Session Risk widget</h3>
            <?= Widget::riskCard(42, 'Warning: suspicious travel and new browser signature detected. Access is monitored.', [
                'failed_logins' => 0,
                'unknown_device' => 15,
                'unknown_browser' => 20,
                'unknown_ip' => 0,
                'rapid_logins' => 7,
                'impossible_travel' => 0,
                'encryption_frequency' => 0,
                'session_anomaly' => 0
            ]) ?>
        </div>

        <div class="space-y-4 md:col-span-2">
            <h3 class="text-xs font-bold text-white uppercase font-mono tracking-widest">3. Heuristic Threat Cards</h3>
            <div class="space-y-4">
                <?= Widget::threatCard('SILENT SMS TRIANGULATION', 75, '185.220.101.4', 'Type 0 silent SMS ping sequence identified from unknown carrier SMSC base network routing.', '2 mins ago') ?>
                <?= Widget::threatCard('IMSI CATCHER SCANNER', 92, '127.0.0.1', 'Mobile receiver indicates carrier footprint changes. Cell towers identifiers mismatch.', '5 mins ago') ?>
            </div>
        </div>
    </div>

    <!-- 3. Quick Actions, Notifications & Timeline Activities -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Quick Actions -->
        <div class="space-y-4">
            <h3 class="text-xs font-bold text-white uppercase font-mono tracking-widest">4. Quick Actions</h3>
            <div class="cyber-card p-6 space-y-3.5">
                <?= Widget::quickAction('Payload Enveloping', APP_URL . '/encrypt-payload', 'lock', 'cyan') ?>
                <?= Widget::quickAction('Decapsulate Cipher', APP_URL . '/decrypt-payload', 'unlock', 'emerald') ?>
                <?= Widget::quickAction('Audit Alarms Feed', APP_URL . '/admin/threats', 'shield-alert', 'rose') ?>
                <?= Widget::quickAction('App Settings', APP_URL . '/admin/settings', 'settings', 'amber') ?>
            </div>
        </div>

        <!-- Notification Cards -->
        <div class="space-y-4">
            <h3 class="text-xs font-bold text-white uppercase font-mono tracking-widest">5. Notifications Desk</h3>
            <div class="cyber-card p-6 space-y-3.5 max-h-[350px] overflow-y-auto">
                <?= Widget::notificationRow('Passcode Rotated', 'Credential baseline successfully changed. Previous hash deactivated.', 'success', '10 mins ago', true) ?>
                <?= Widget::notificationRow('Suspicious Subnet', 'Connection established from temporary VPN routing IP address block.', 'warning', '1 hour ago', false) ?>
                <?= Widget::notificationRow('Signature Error', 'HMAC integrity signature verification failed on cipher decapsulation.', 'error', '2 hours ago', false) ?>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="space-y-4">
            <h3 class="text-xs font-bold text-white uppercase font-mono tracking-widest">6. Activity Timelines</h3>
            <div class="cyber-card p-6 space-y-6 max-h-[350px] overflow-y-auto">
                <?= Widget::activityItem('login_success (Device: Desktop, Browser: Chrome)', '127.0.0.1', '3 mins ago', 'monitor') ?>
                <?= Widget::activityItem('encrypt_payload_transmission (AES-256-CBC)', '127.0.0.1', '12 mins ago', 'lock') ?>
                <?= Widget::activityItem('database_backup_generation', '127.0.0.1', '1 hour ago', 'database-backup') ?>
                <?= Widget::activityItem('admin_suspension_toggle (User ID: 4)', '127.0.0.1', '3 hours ago', 'user-cog') ?>
            </div>
        </div>

    </div>

    <!-- 4. Reusable Tables & Reusable Chart Canvas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Reusable Tables Widget -->
        <div class="space-y-4">
            <h3 class="text-xs font-bold text-white uppercase font-mono tracking-widest">7. Reusable Schema Tables</h3>
            <?= Widget::table('Carrier Registry logs', ['Node ID', 'Carrier Target', 'Status', 'Timestamp'], [
                ['1', 'MTN Nigeria', 'NOMINAL', '2026-07-11 12:00'],
                ['2', 'Airtel Nigeria', 'ATTACKED', '2026-07-11 12:05'],
                ['3', 'Globacom', 'NOMINAL', '2026-07-11 12:10']
            ], 'server') ?>
        </div>

        <!-- Reusable Chart Widget -->
        <div class="space-y-4">
            <h3 class="text-xs font-bold text-white uppercase font-mono tracking-widest">8. Reusable Charts Container</h3>
            <?= Widget::chartContainer('showroomChart', 'Real-Time Heuristic Growth', 'line-chart') ?>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Render Showroom Chart
    const ctx = document.getElementById('showroomChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['12:00', '12:10', '12:20', '12:30', '12:40', '12:50', '13:00'],
            datasets: [{
                data: [4, 15, 8, 25, 12, 35, 18],
                borderColor: '#06b6d4',
                backgroundColor: 'rgba(6, 182, 212, 0.05)',
                borderWidth: 2,
                tension: 0.35,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#888888', font: { family: 'Fira Code', size: 9 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#888888', font: { family: 'Fira Code', size: 9 } }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
