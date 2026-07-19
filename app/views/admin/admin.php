<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Generate CSRF token
$csrfToken = \App\Core\Session::generateCSRFToken();
?>
<div class="space-y-8 animate-fade-in">
    <!-- Admin Controls Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center p-6 rounded-2xl border gap-4" style="background-color: var(--color-surface); border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2 font-mono uppercase">
                <span>Security Control Terminal</span>
                <span class="text-red-500 font-mono font-bold">[ROOT]</span>
            </h1>
            <p class="text-xs mt-1" style="color: var(--color-foreground-muted);">Systems: Running &middot; Database Backups Saved &middot; Security Alerts Active</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= APP_URL ?>/admin/users" class="btn-secondary text-xs py-1.5 px-3" title="Click to view and manage all user accounts.">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span>User Directory</span>
            </a>
            <a href="<?= APP_URL ?>/admin/threats" class="btn-secondary text-xs py-1.5 px-3" title="Click to view the central alarm feed for security alerts.">
                <i data-lucide="shield-alert" class="w-4 h-4 text-red-500 animate-pulse"></i>
                <span>Alerts Center</span>
            </a>
            <a href="<?= APP_URL ?>/admin/behavior" class="btn-secondary text-xs py-1.5 px-3" title="Click to monitor user activities and identify unusual habits.">
                <i data-lucide="activity" class="w-4 h-4"></i>
                <span>User Behavior</span>
            </a>
            <a href="<?= APP_URL ?>/admin/sessions" class="btn-secondary text-xs py-1.5 px-3" title="Click to view and end active user login sessions.">
                <i data-lucide="key-round" class="w-4 h-4 text-cyan-400"></i>
                <span>Active Sessions</span>
            </a>
            <a href="<?= APP_URL ?>/admin/reports/central" class="btn-secondary text-xs py-1.5 px-3" title="Click to generate and download system security reports.">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Reports Control</span>
            </a>
            <a href="<?= APP_URL ?>/admin/settings" class="btn-secondary text-xs py-1.5 px-3" title="Click to configure system settings and options.">
                <i data-lucide="settings" class="w-4 h-4"></i>
                <span>System Settings</span>
            </a>
            <a href="<?= APP_URL ?>/admin/backups" class="btn-secondary text-xs py-1.5 px-3" title="Click to manage database backups and restore saved configurations.">
                <i data-lucide="database-backup" class="w-4 h-4"></i>
                <span>Backups & Recovery</span>
            </a>
            <a href="<?= APP_URL ?>/admin/audit" class="btn-secondary text-xs py-1.5 px-3" title="Click to view the master log ledger of all events.">
                <i data-lucide="file-check-2" class="w-4 h-4"></i>
                <span>Activity History</span>
            </a>
            <a href="<?= APP_URL ?>/admin/analytics" class="btn-secondary text-xs py-1.5 px-3" title="Click to view visual charts and graphs of security alerts.">
                <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                <span>Alert Analytics</span>
            </a>
            <a href="<?= APP_URL ?>/admin/widgets" class="btn-secondary text-xs py-1.5 px-3" title="Click to view the showroom of UI cards and layout widgets.">
                <i data-lucide="layout-template" class="w-4 h-4"></i>
                <span>UI Cards</span>
            </a>
        </div>
    </div>

    <!-- Administrative Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-6">
        <!-- Total Users -->
        <div class="cyber-card flex items-center justify-between" title="The total number of registered users and operators on the platform.">
            <div>
                <span class="text-[10px] font-bold block uppercase tracking-wider mb-1" style="color: var(--color-foreground-muted);">Operators</span>
                <span class="text-2xl font-bold text-white font-mono"><?= $metrics['total_users'] ?></span>
            </div>
            <div class="p-2 bg-slate-900 border border-slate-800 rounded text-slate-400">
                <i data-lucide="users" class="w-4 h-4"></i>
            </div>
        </div>

        <!-- Total Admins -->
        <div class="cyber-card flex items-center justify-between" title="The total number of users with admin power on the platform.">
            <div>
                <span class="text-[10px] font-bold block uppercase tracking-wider mb-1" style="color: var(--color-foreground-muted);">Admins</span>
                <span class="text-2xl font-bold text-red-500 font-mono"><?= $metrics['total_admins'] ?></span>
            </div>
            <div class="p-2 bg-slate-900 border border-slate-800 rounded text-red-400">
                <i data-lucide="user-cog" class="w-4 h-4"></i>
            </div>
        </div>

        <!-- AI Threats -->
        <div class="cyber-card flex items-center justify-between" title="The total number of network threats found by the AI engine.">
            <div>
                <span class="text-[10px] font-bold block uppercase tracking-wider mb-1" style="color: var(--color-foreground-muted);">AI Threats</span>
                <span class="text-2xl font-bold text-white font-mono"><?= $metrics['total_threats'] ?></span>
            </div>
            <div class="p-2 bg-slate-900 border border-slate-800 rounded text-amber-500">
                <i data-lucide="activity" class="w-4 h-4"></i>
            </div>
        </div>

        <!-- Critical Alarms -->
        <div class="cyber-card flex items-center justify-between" title="The number of active high or critical security alerts.">
            <div>
                <span class="text-[10px] font-bold block uppercase tracking-wider mb-1" style="color: var(--color-foreground-muted);">Alarms</span>
                <span class="text-2xl font-bold text-rose-500 font-mono animate-pulse"><?= $metrics['critical_alerts'] ?></span>
            </div>
            <div class="p-2 bg-slate-900 border border-slate-800 rounded text-rose-500">
                <i data-lucide="shield-alert" class="w-4 h-4"></i>
            </div>
        </div>

        <!-- Traced Envelopes -->
        <div class="cyber-card flex items-center justify-between" title="The total number of encrypted messages locked on the platform.">
            <div>
                <span class="text-[10px] font-bold block uppercase tracking-wider mb-1" style="color: var(--color-foreground-muted);">Envelopes</span>
                <span class="text-2xl font-bold text-white font-mono"><?= $metrics['encrypted_count'] ?></span>
            </div>
            <div class="p-2 bg-slate-900 border border-slate-800 rounded text-cyan-400">
                <i data-lucide="mail" class="w-4 h-4"></i>
            </div>
        </div>
    </div>

    <!-- Layout: System Health, Recent Registrations & Logins -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- System Health Baseline -->
        <div class="cyber-card p-6 space-y-4" title="Shows if the database, logs folder, backups folder, and firewall are running correctly.">
            <h3 class="text-xs font-bold uppercase tracking-wider text-white font-mono flex items-center gap-1.5 border-b pb-3" style="border-color: var(--color-border);">
                <i data-lucide="heart" class="w-4.5 h-4.5 text-red-500 animate-pulse"></i>
                <span>System Health</span>
            </h3>
            
            <div class="space-y-3 font-mono text-2xs">
                <div class="flex justify-between border-b pb-2" style="border-color: var(--color-border);">
                    <span style="color: var(--color-foreground-muted);">DATABASE STATUS:</span>
                    <span class="text-emerald-400 font-bold"><?= $systemHealth['database'] ?></span>
                </div>
                <div class="flex justify-between border-b pb-2" style="border-color: var(--color-border);">
                    <span style="color: var(--color-foreground-muted);">LOGS WRITE CAPABILITY:</span>
                    <span class="<?= $systemHealth['logs_directory'] === 'WRITABLE' ? 'text-emerald-400' : 'text-rose-500' ?> font-bold"><?= $systemHealth['logs_directory'] ?></span>
                </div>
                <div class="flex justify-between border-b pb-2" style="border-color: var(--color-border);">
                    <span style="color: var(--color-foreground-muted);">BACKUPS WRITE CAPABILITY:</span>
                    <span class="<?= $systemHealth['backups_directory'] === 'WRITABLE' ? 'text-emerald-400' : 'text-rose-500' ?> font-bold"><?= $systemHealth['backups_directory'] ?></span>
                </div>
                <div class="flex justify-between">
                    <span style="color: var(--color-foreground-muted);">FIREWALL SECURITY:</span>
                    <span class="text-emerald-400 font-bold">SAFE</span>
                </div>
            </div>
        </div>

        <!-- Recent Logins -->
        <div class="cyber-card p-6 space-y-4" title="List of users who logged in recently and their IP addresses.">
            <h3 class="text-xs font-bold uppercase tracking-wider text-white font-mono flex items-center gap-1.5 border-b pb-3" style="border-color: var(--color-border);">
                <i data-lucide="history" class="w-4.5 h-4.5 text-cyan-400"></i>
                <span>Recent Logins</span>
            </h3>
            
            <div class="space-y-3 font-mono text-2xs">
                <?php if (empty($recentLogins)): ?>
                    <div class="text-center py-4" style="color: var(--color-foreground-muted);">No login events logged.</div>
                <?php else: ?>
                    <?php foreach ($recentLogins as $rl): ?>
                        <div class="flex justify-between border-b pb-2" style="border-color: var(--color-border);">
                            <span class="text-white font-bold"><?= htmlspecialchars($rl['username']) ?></span>
                            <span style="color: var(--color-foreground-muted);"><?= htmlspecialchars($rl['ip_address']) ?> &middot; <?= date('H:i m-d', strtotime($rl['created_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Registrations -->
        <div class="cyber-card p-6 space-y-4" title="List of newly created user accounts on this system.">
            <h3 class="text-xs font-bold uppercase tracking-wider text-white font-mono flex items-center gap-1.5 border-b pb-3" style="border-color: var(--color-border);">
                <i data-lucide="user-plus" class="w-4.5 h-4.5 text-emerald-400"></i>
                <span>Recent Registrations</span>
            </h3>
            
            <div class="space-y-3 font-mono text-2xs">
                <?php if (empty($recentRegistrations)): ?>
                    <div class="text-center py-4" style="color: var(--color-foreground-muted);">No operator nodes registered.</div>
                <?php else: ?>
                    <?php foreach ($recentRegistrations as $rr): ?>
                        <div class="flex justify-between border-b pb-2" style="border-color: var(--color-border);">
                            <span class="text-white font-bold"><?= htmlspecialchars($rr['username']) ?></span>
                            <span style="color: var(--color-foreground-muted);"><?= date('Y-m-d', strtotime($rr['created_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Workspace Layout: Controls & Logs -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        
        <!-- Left: Threat Chart & User Management -->
        <div class="space-y-8 xl:col-span-1">
            <!-- Threat Vector Doughnut -->
            <div class="cyber-card p-6" title="A chart showing the breakdown of different types of threats.">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2 font-mono mb-4">
                    <i data-lucide="bar-chart-3" class="w-4 h-4" style="color: var(--color-primary);"></i>
                    <span>Threat Vector Analytics</span>
                </h3>
                
                <div class="relative w-full h-[220px] flex items-center justify-center">
                    <?php if (empty($threatStats)): ?>
                        <div class="text-2xs text-gray-500 font-mono">No threat telemetry recorded yet.</div>
                    <?php else: ?>
                        <canvas id="threatChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Operator Accounts Control -->
            <div class="cyber-card p-6 space-y-4" title="Manage status options and roles of user accounts.">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2 font-mono border-b pb-3" style="border-color: var(--color-border);">
                    <i data-lucide="user-cog" class="w-4 h-4" style="color: var(--color-primary);"></i>
                    <span>User Accounts Control</span>
                </h3>

                <div class="space-y-3 overflow-y-auto max-h-[300px]">
                    <?php foreach ($users as $u): ?>
                        <div class="p-3 border rounded-xl flex items-center justify-between text-xs font-mono" style="background-color: rgba(0,0,0,0.15); border-color: var(--color-border);">
                            <div>
                                <span class="text-white font-bold block"><?= htmlspecialchars($u['username']) ?></span>
                                <span class="text-[10px] block" style="color: var(--color-foreground-muted);"><?= htmlspecialchars($u['email']) ?></span>
                                <span class="px-2 py-0.5 rounded text-[8px] font-bold tracking-wider inline-block mt-1 <?= $u['role'] === 'admin' ? 'bg-red-500/10 text-red-400' : 'bg-cyan-500/10 text-cyan-400' ?>">
                                    <?= strtoupper($u['role']) ?>
                                </span>
                                <span class="px-2 py-0.5 rounded text-[8px] font-bold tracking-wider inline-block mt-1 <?= $u['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400 animate-pulse' ?>">
                                    <?= strtoupper($u['status']) ?>
                                </span>
                            </div>

                            <!-- Suspension button -->
                            <?php if ($u['id'] !== $user['id']): ?>
                                <button onclick="toggleUserStatus(<?= $u['id'] ?>, '<?= $u['status'] === 'active' ? 'suspend' : 'activate' ?>')"
                                        title="<?= $u['status'] === 'active' ? 'Click to freeze this account so the user cannot log in.' : 'Click to unfreeze this account so the user can log in again.' ?>"
                                        class="py-1 px-2.5 rounded font-semibold text-[10px] cursor-pointer transition-all border <?= $u['status'] === 'active' ? 'border-rose-500/30 hover:bg-rose-500/20 text-rose-400 bg-rose-500/5' : 'border-emerald-500/30 hover:bg-emerald-500/20 text-emerald-400 bg-emerald-500/5' ?>">
                                    <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                </button>
                            <?php else: ?>
                                <span class="text-[9px] font-mono italic" style="color: var(--color-foreground-muted);">ROOT</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Middle & Right: System Auditing Trail & SQL Backups -->
        <div class="space-y-8 xl:col-span-2">
            <!-- Database Backup and Disaster Recovery -->
            <div class="cyber-card p-6 space-y-4" title="Manage database backups and restore configurations from saved snapshots.">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b pb-3 gap-2" style="border-color: var(--color-border);">
                    <div>
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2 font-mono">
                            <i data-lucide="database-backup" class="w-4.5 h-4.5" style="color: var(--color-primary);"></i>
                            <span>Database Backups</span>
                        </h3>
                        <span class="text-[9px]" style="color: var(--color-foreground-muted);">Create database backups and restore saved data</span>
                    </div>

                    <button onclick="triggerBackup()" title="Click to create a new backup copy of all data in the database." class="btn-primary text-xs py-2 px-4 cursor-pointer">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Create Backup</span>
                    </button>
                </div>

                <!-- Backup snapshots list -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[220px] overflow-y-auto pr-1">
                    <?php if (empty($backups)): ?>
                        <div class="col-span-2 text-center text-xs font-mono py-4" style="color: var(--color-foreground-muted);">No snapshots registered. Click above to backup.</div>
                    <?php else: ?>
                        <?php foreach ($backups as $b): ?>
                            <div class="p-3 border rounded-xl flex items-center justify-between text-xs font-mono" style="background-color: rgba(0,0,0,0.1); border-color: var(--color-border);">
                                <div class="max-w-[200px]">
                                    <span class="text-white block truncate" title="<?= htmlspecialchars($b['filename']) ?>"><?= htmlspecialchars($b['filename']) ?></span>
                                    <span class="text-[9px] block mt-1" style="color: var(--color-foreground-muted);"><?= $b['created_at'] ?> &middot; <?= $b['size'] ?></span>
                                </div>
                                <button onclick="triggerRestore('<?= htmlspecialchars($b['filename']) ?>')"
                                        title="Click to overwrite the current database with the data in this backup snapshot."
                                        class="btn-secondary text-[9px] py-1 px-2.5 cursor-pointer">
                                    Restore
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Master Activity Log Audit Trail -->
            <div class="cyber-card p-6 space-y-4" title="A list of all actions performed by users and the system.">
                <h3 class="text-xs font-bold uppercase tracking-wider text-white flex items-center justify-between font-mono">
                    <span>Master Activity History</span>
                    <i data-lucide="newspaper" class="w-4 h-4" style="color: var(--color-primary);"></i>
                </h3>

                <div class="overflow-x-auto max-h-[380px] overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse" style="color: var(--color-foreground);">
                        <thead>
                            <tr class="border-b font-mono text-[10px]" style="border-color: var(--color-border); color: var(--color-primary);">
                                <th class="pb-2 font-medium">User ID</th>
                                <th class="pb-2 font-medium">Username</th>
                                <th class="pb-2 font-medium">Activity Performed</th>
                                <th class="pb-2 font-medium">Date</th>
                                <th class="pb-2 font-medium">Time</th>
                                <th class="pb-2 font-medium">IP Address</th>
                                <th class="pb-2 font-medium">System Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="divide-color: var(--color-border);">
                            <?php foreach ($logs as $log): ?>
                                <?php 
                                    $riskColor = 'text-emerald-400';
                                    if ($log['risk_score'] >= 70) $riskColor = 'text-rose-500 font-bold';
                                    elseif ($log['risk_score'] >= 30) $riskColor = 'text-amber-500';
                                ?>
                                <tr class="hover:bg-slate-800/10 transition-colors text-gray-300">
                                    <td class="py-3 font-mono text-[10px] text-white"><?= htmlspecialchars($log['user_id'] ?? 'SYSTEM') ?></td>
                                    <td class="py-3 font-mono font-bold text-white"><?= htmlspecialchars($log['username'] ?? 'SYSTEM') ?></td>
                                    <td class="py-3 font-semibold" style="color: var(--color-foreground-title);"><?= htmlspecialchars($log['action']) ?></td>
                                    <td class="py-3 font-mono text-[10px]" style="color: var(--color-foreground-muted);"><?= date('Y-m-d', strtotime($log['created_at'])) ?></td>
                                    <td class="py-3 font-mono text-[10px]" style="color: var(--color-foreground-muted);"><?= date('H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td class="py-3 font-mono" style="color: var(--color-foreground-muted);"><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></td>
                                    <td class="py-3" style="color: var(--color-foreground-title);">
                                        <span class="<?= $riskColor ?>"><?= htmlspecialchars($log['threat_classification']) ?></span>
                                        <span class="text-3xs font-mono opacity-50">(<?= $log['risk_score'] ?>% Risk)</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle suspension action
async function toggleUserStatus(userId, action) {
    if (!confirm(`Confirm status change for user ID: ${userId}?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('status_action', action);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/admin/suspend', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Operation failed: ' + (data.message || 'Suspension failed'));
        }
    } catch (e) {
        alert('Network communication failure.');
    }
}

// Generate Backup Snapshot
async function triggerBackup() {
    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/admin/backup', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Backup failed: ' + (data.message || 'Error occurred'));
        }
    } catch(e) {
        alert('Backup failed due to network error.');
    }
}

// Restore Snapshot
async function triggerRestore(filename) {
    if (!confirm(`CAUTION: Restoring will overwrite current data with the backup "${filename}". Confirm database roll back?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('filename', filename);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/admin/restore', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Restoration failed: ' + (data.message || 'Database error'));
        }
    } catch(e) {
        alert('Restoration network execution failure.');
    }
}

// Render Chart.js analytics logic
<?php if (!empty($threatStats)): ?>
document.addEventListener("DOMContentLoaded", () => {
    const rawData = <?= json_encode($threatStats) ?>;
    const labels = rawData.map(item => item.threat_classification);
    const counts = rawData.map(item => item.count);

    const ctx = document.getElementById('threatChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: [
                    'rgba(244, 63, 94, 0.65)',  // Rose/Critical
                    'rgba(245, 158, 11, 0.65)', // Amber/Warning
                    'rgba(6, 182, 212, 0.65)',  // Cyan/Info
                    'rgba(168, 85, 247, 0.65)'  // Purple/Anomaly
                ],
                borderColor: '#0b0f19',
                borderWidth: 1.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#9ca3af',
                        font: {
                            family: 'Fira Code',
                            size: 10
                        },
                        boxWidth: 12
                    }
                }
            }
        }
    });
});
<?php endif; ?>
</script>
