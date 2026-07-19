<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$csrfToken = \App\Core\Session::generateCSRFToken();
?>
<div class="space-y-8 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-4 border-b border-slate-800 gap-4" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase">Disaster Recovery & Backups Center</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Manage manual snapshots, configure automated schedulers, and verify file integrity hashes</span>
        </div>
        <div class="flex gap-2">
            <a href="<?= APP_URL ?>/admin/backups/wizard" class="btn-primary text-xs py-1.5 px-3">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                <span>Restore Wizard</span>
            </a>
            <a href="<?= APP_URL ?>/admin" class="btn-secondary text-xs py-1.5 px-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Terminal</span>
            </a>
        </div>
    </div>

    <!-- Alert Message Widget -->
    <div id="backupAlert" class="hidden p-4 rounded-xl text-xs font-mono font-medium border flex items-start space-x-2">
        <i id="alertIcon" class="w-4 h-4 mt-0.5"></i>
        <span id="alertMessage" class="flex-1"></span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Manual Backup Creator -->
        <div class="cyber-card p-6 space-y-4">
            <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b pb-3" style="border-color: var(--color-border);">
                <i data-lucide="plus-circle" class="w-4 h-4" style="color: var(--color-primary);"></i>
                <span>Manual Snapshot Trigger</span>
            </h3>

            <form id="generateBackupForm" class="space-y-4 text-xs">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>" />

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Backup Target</label>
                    <select name="backup_type" class="cyber-input py-1.5 font-mono">
                        <option value="database">Normalized Database Tables</option>
                        <option value="logs">WAF & Security Activity Logs</option>
                    </select>
                </div>

                <div class="flex items-center space-x-2 font-mono text-gray-300">
                    <input type="checkbox" name="encrypt" id="encrypt" value="1" class="rounded bg-slate-900 border-slate-850 text-cyan-500 focus:ring-0" checked />
                    <label for="encrypt">Encrypt Dump (AES-256-CBC)</label>
                </div>

                <button type="submit" class="w-full btn-primary py-2.5 justify-center font-mono uppercase tracking-wider">
                    <span>Deploy Snapshot</span>
                    <i data-lucide="server" class="w-4 h-4"></i>
                </button>
            </form>
        </div>

        <!-- Automatic Scheduler Details -->
        <div class="cyber-card p-6 space-y-4 lg:col-span-2">
            <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b pb-3" style="border-color: var(--color-border);">
                <i data-lucide="calendar-clock" class="w-4 h-4" style="color: var(--color-primary);"></i>
                <span>Automated Schedulers Configurations</span>
            </h3>

            <div class="space-y-4 text-xs font-mono">
                <div class="p-4 rounded-xl border border-cyan-500/20 bg-cyan-500/5 text-cyan-400 flex items-start gap-3">
                    <i data-lucide="clock" class="w-5 h-5 mt-0.5 animate-pulse"></i>
                    <div>
                        <strong class="block uppercase">Daily Automated Cron Active</strong>
                        <span>The server automatically triggers an encrypted database & logs backup daily at 02:00 AM server local time. Retention threshold: 30 days.</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 border rounded-xl" style="background-color: rgba(0,0,0,0.1); border-color: var(--color-border);">
                        <span style="color: var(--color-foreground-muted);">Scheduler rule:</span>
                        <span class="text-white font-bold block mt-0.5">0 2 * * *</span>
                    </div>
                    <div class="p-3 border rounded-xl" style="background-color: rgba(0,0,0,0.1); border-color: var(--color-border);">
                        <span style="color: var(--color-foreground-muted);">Last Cron run:</span>
                        <span class="text-white block mt-0.5">2026-07-11 02:00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Snapshots History -->
    <div class="cyber-card p-6">
        <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-4 font-mono flex items-center justify-between">
            <span>System Snapshots Ledger History</span>
            <i data-lucide="archive" class="w-4.5 h-4.5 text-cyan-400"></i>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b font-mono text-[10px]" style="border-color: var(--color-border); color: var(--color-primary);">
                        <th class="pb-2.5">Snapshot Filename</th>
                        <th class="pb-2.5">File Size</th>
                        <th class="pb-2.5">Security Level</th>
                        <th class="pb-2.5">Operator Creator</th>
                        <th class="pb-2.5">Created Timestamp</th>
                        <th class="pb-2.5 text-right">Ledger Actions Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="divide-color: var(--color-border);">
                    <?php if (empty($backups)): ?>
                        <tr>
                            <td colspan="6" class="py-4 text-center" style="color: var(--color-foreground-muted);">No backups registered in history.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($backups as $b): ?>
                            <?php 
                                $isEncrypted = strpos($b['filename'], '.enc.') !== false;
                            ?>
                            <tr class="hover:bg-slate-800/10 transition-colors text-gray-300">
                                <td class="py-3 font-bold text-white font-mono max-w-[220px] truncate" title="<?= htmlspecialchars($b['filename']) ?>"><?= htmlspecialchars($b['filename']) ?></td>
                                <td class="py-3 font-mono"><?= htmlspecialchars($b['filesize']) ?></td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold font-mono tracking-wider <?= $isEncrypted ? 'bg-cyan-500/10 text-cyan-400' : 'bg-amber-500/10 text-amber-500' ?>">
                                        <?= $isEncrypted ? 'AES-256 ENCRYPTED' : 'UNENCRYPTED' ?>
                                    </span>
                                </td>
                                <td class="py-3 font-mono text-cyan-400 font-bold"><?= htmlspecialchars($b['creator'] ?? 'SYSTEM') ?></td>
                                <td class="py-3 font-mono" style="color: var(--color-foreground-muted);"><?= date('Y-m-d H:i', strtotime($b['created_at'])) ?></td>
                                <td class="py-3 text-right space-x-1.5">
                                    <a href="<?= APP_URL ?>/admin/backups/download?filename=<?= urlencode($b['filename']) ?>" class="py-1 px-2 border border-slate-700 hover:bg-slate-800 text-gray-300 rounded text-[10px] font-bold cursor-pointer font-mono">Get</a>
                                    <button onclick="verifySnapshot('<?= htmlspecialchars($b['filename']) ?>')" class="py-1 px-2 border border-cyan-500/30 hover:bg-cyan-500/10 text-cyan-400 rounded text-[10px] font-bold cursor-pointer font-mono">Verify</button>
                                    <button onclick="restoreSnapshot('<?= htmlspecialchars($b['filename']) ?>')" class="py-1 px-2 border border-amber-500/30 hover:bg-amber-500/10 text-amber-500 rounded text-[10px] font-bold cursor-pointer font-mono">Rollback</button>
                                    <button onclick="deleteSnapshot('<?= htmlspecialchars($b['filename']) ?>')" class="py-1 px-2 border border-rose-500/30 hover:bg-rose-500/10 text-rose-400 rounded text-[10px] font-bold cursor-pointer font-mono">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Generate Snapshot
document.getElementById('generateBackupForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const alertBox = document.getElementById('backupAlert');
    const alertMessage = document.getElementById('alertMessage');
    const alertIcon = document.getElementById('alertIcon');

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/admin/backups/generate', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-check');
            alertMessage.textContent = data.message;
            alertBox.classList.remove('hidden');
            lucide.createIcons();
            
            setTimeout(() => window.location.reload(), 1000);
        } else {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-red-500/30 bg-red-500/10 text-red-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-x');
            alertMessage.textContent = data.message || 'Manual backup dump failed.';
            alertBox.classList.remove('hidden');
            lucide.createIcons();
        }
    } catch (e) {
        alert('Gateway communication failure.');
    }
});

// Verify Integrity
async function verifySnapshot(filename) {
    const alertBox = document.getElementById('backupAlert');
    const alertMessage = document.getElementById('alertMessage');
    const alertIcon = document.getElementById('alertIcon');

    try {
        const formData = new FormData();
        formData.append('filename', filename);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/admin/backups/verify', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            if (data.check === 'verified') {
                alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 flex items-start space-x-2";
                alertIcon.setAttribute('data-lucide', 'shield-check');
            } else {
                alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-red-500/30 bg-red-500/10 text-red-400 flex items-start space-x-2";
                alertIcon.setAttribute('data-lucide', 'shield-x');
            }
            alertMessage.innerHTML = `<strong>Checksum Audit Result:</strong> ${data.message}<br/><span class="text-[10px] text-gray-400">SHA-256: ${data.hash}</span>`;
            alertBox.classList.remove('hidden');
            lucide.createIcons();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            alert('Verification execution failed.');
        }
    } catch(e) {
        alert('Network execution failure.');
    }
}

// Restore Snapshot
async function restoreSnapshot(filename) {
    if (!confirm(`CAUTION: Restoring snapshot "${filename}" will overwrite all current tables and revert modifications. Proceed with database rollback?`)) {
        return;
    }
    const alertBox = document.getElementById('backupAlert');
    const alertMessage = document.getElementById('alertMessage');
    const alertIcon = document.getElementById('alertIcon');

    try {
        const formData = new FormData();
        formData.append('filename', filename);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/admin/backups/restore', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-check');
            alertMessage.textContent = data.message;
            alertBox.classList.remove('hidden');
            lucide.createIcons();

            setTimeout(() => window.location.reload(), 1500);
        } else {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-red-500/30 bg-red-500/10 text-red-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-x');
            alertMessage.textContent = data.message || 'Restoration failed.';
            alertBox.classList.remove('hidden');
            lucide.createIcons();
        }
    } catch (e) {
        alert('Network execution failure.');
    }
}

// Delete Snapshot
async function deleteSnapshot(filename) {
    if (!confirm(`Are you sure you want to permanently delete snapshot "${filename}"?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('filename', filename);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/admin/backups/delete', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Failed to delete backup.');
        }
    } catch(e) {
        alert('Network execution failure.');
    }
}
</script>
