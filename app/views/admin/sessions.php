<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$csrfToken = \App\Core\Session::generateCSRFToken();
$currentSessionId = session_id();

// Count total rotations from logs
$totalRotations = 0;
foreach ($sessionHistory as $hist) {
    if ($hist['action'] === 'session_rotated') {
        $totalRotations++;
    }
}
?>
<div class="space-y-8 animate-fade-in font-mono text-xs text-gray-300">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center pb-6 border-b border-cyan-500/10 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="key-round" class="w-7 h-7 text-cyan-400"></i>
                <span>Enterprise Session Control Center</span>
            </h1>
            <span class="text-xs text-gray-400">SIEM Global Session Gating &bull; Decrypting Device footprints &bull; Active Revocation Ledgers</span>
        </div>
        <a href="<?= APP_URL ?>/admin" class="btn-secondary text-xs py-2 px-4 flex items-center gap-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Return to Terminal</span>
        </a>
    </div>

    <!-- Administrative Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6">
        <div class="cyber-card p-4 flex items-center justify-between">
            <div>
                <span class="text-[9px] font-bold block uppercase tracking-wider mb-1 text-gray-400">Global Session Links</span>
                <span class="text-2xl font-bold text-white"><?= count($sessions) ?></span>
            </div>
            <div class="p-2 bg-slate-900 border border-slate-800 rounded text-cyan-400">
                <i data-lucide="network" class="w-4 h-4"></i>
            </div>
        </div>

        <div class="cyber-card p-4 flex items-center justify-between">
            <div>
                <span class="text-[9px] font-bold block uppercase tracking-wider mb-1 text-gray-400">Concurrent Violations</span>
                <span class="text-2xl font-bold <?= count($concurrentSessions) > 0 ? 'text-red-500 animate-pulse' : 'text-emerald-400' ?>"><?= count($concurrentSessions) ?></span>
            </div>
            <div class="p-2 bg-slate-900 border border-slate-800 rounded <?= count($concurrentSessions) > 0 ? 'text-red-500' : 'text-emerald-400' ?>">
                <i data-lucide="users-round" class="w-4 h-4"></i>
            </div>
        </div>

        <div class="cyber-card p-4 flex items-center justify-between">
            <div>
                <span class="text-[9px] font-bold block uppercase tracking-wider mb-1 text-gray-400">Rotation Audits</span>
                <span class="text-2xl font-bold text-white"><?= $totalRotations ?></span>
            </div>
            <div class="p-2 bg-slate-900 border border-slate-800 rounded text-purple-400">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
            </div>
        </div>

        <div class="cyber-card p-4 flex items-center justify-between">
            <div>
                <span class="text-[9px] font-bold block uppercase tracking-wider mb-1 text-gray-400">WAF Headers State</span>
                <span class="text-2xl font-bold text-emerald-400">SECURE</span>
            </div>
            <div class="p-2 bg-slate-900 border border-slate-800 rounded text-emerald-400">
                <i data-lucide="shield" class="w-4 h-4"></i>
            </div>
        </div>
    </div>

    <!-- Alert Panel (Warnings & Violations) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Concurrent Session Gating -->
        <div class="cyber-card p-6 space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-2 flex items-center justify-between border-b border-cyan-500/10 pb-3">
                <span class="flex items-center gap-1.5 text-red-500">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500 animate-bounce"></i>
                    <span>Concurrent Login Violations</span>
                </span>
                <span class="text-[8px] bg-red-500/15 text-red-400 border border-red-500/30 px-2 py-0.5 rounded uppercase font-bold">Action Needed</span>
            </h3>

            <div class="space-y-3 font-mono text-xs">
                <?php if (empty($concurrentSessions)): ?>
                    <div class="text-center py-6 text-gray-500 border border-dashed border-gray-900 rounded-xl">
                        No concurrent login violations identified in this session cycle.
                    </div>
                <?php else: ?>
                    <?php foreach ($concurrentSessions as $cs): ?>
                        <div class="p-4 rounded-xl border border-red-500/20 bg-red-500/5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="space-y-1">
                                <strong class="text-white text-xs block uppercase">Operator node: <?= htmlspecialchars($cs['username']) ?></strong>
                                <span class="text-[10px] text-red-400 font-bold block"><?= $cs['session_count'] ?> concurrent active connections detected.</span>
                            </div>
                            <button type="button" onclick="terminateUserSessions(<?= $cs['user_id'] ?>, '<?= htmlspecialchars($cs['username']) ?>')" class="py-1.5 px-3 bg-red-650 hover:bg-red-500 text-white rounded font-bold uppercase text-[9px] tracking-wider transition-all cursor-pointer">
                                Force Kill All Sessions
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Session Security Alerts list -->
        <div class="cyber-card p-6 space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-2 flex items-center justify-between border-b border-cyan-500/10 pb-3">
                <span class="flex items-center gap-1.5">
                    <i data-lucide="shield-alert" class="w-4 h-4 text-cyan-400 animate-pulse"></i>
                    <span>SIEM Security Alerts (Session-Related)</span>
                </span>
                <i data-lucide="activity" class="w-4 h-4 text-cyan-500/50"></i>
            </h3>

            <div class="space-y-3 font-mono text-xs max-h-[190px] overflow-y-auto pr-1">
                <?php if (empty($securityAlerts)): ?>
                    <div class="text-center py-6 text-gray-500">No session security alerts cataloged.</div>
                <?php else: ?>
                    <?php foreach ($securityAlerts as $alert): ?>
                        <div class="p-3 border border-gray-900 rounded-xl bg-gray-950/40 space-y-1">
                            <div class="flex justify-between items-start">
                                <span class="px-2 py-0.5 rounded text-[8px] font-bold uppercase <?= $alert['severity'] === 'critical' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/40' : ($alert['severity'] === 'high' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/40' : 'bg-blue-500/20 text-blue-400 border border-blue-500/40') ?>">
                                    <?= htmlspecialchars($alert['severity']) ?>
                                </span>
                                <span class="text-[9px] text-gray-500"><?= date('Y-m-d H:i', strtotime($alert['created_at'])) ?></span>
                            </div>
                            <p class="text-[10px] text-white font-bold leading-relaxed pt-1">
                                Operator: <?= htmlspecialchars($alert['username'] ?? 'Anonymous') ?> &bull; <?= htmlspecialchars($alert['message']) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Device Recognition Analytics -->
    <div class="cyber-card p-6 space-y-6">
        <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-2 flex items-center justify-between border-b border-cyan-500/10 pb-3">
            <span class="flex items-center gap-1.5">
                <i data-lucide="bar-chart-3" class="w-4 h-4 text-cyan-400"></i>
                <span>Device Recognition & Platform Breakdown</span>
            </span>
            <span class="text-[9px] text-gray-500">Decoded Operating System & Browser profiles</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 font-mono text-xs">
            <!-- OS Footprint -->
            <div class="space-y-4">
                <h4 class="text-white text-xs font-bold border-l-2 border-cyan-500 pl-2">Operating System Distribution</h4>
                <div class="space-y-3 pt-1">
                    <?php 
                    $totalDevices = array_sum($deviceStats);
                    foreach ($deviceStats as $osName => $osCount):
                        $osPercent = $totalDevices > 0 ? round(($osCount / $totalDevices) * 100) : 0;
                    ?>
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-center text-[10px]">
                                <span class="font-bold text-gray-400"><?= htmlspecialchars($osName) ?></span>
                                <span class="text-cyan-400 font-bold"><?= $osCount ?> Sessions (<?= $osPercent ?>%)</span>
                            </div>
                            <div class="w-full bg-gray-900 border border-gray-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-cyan-500 h-full rounded-full" style="width: <?= $osPercent ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Browser Footprint -->
            <div class="space-y-4">
                <h4 class="text-white text-xs font-bold border-l-2 border-cyan-500 pl-2">Browser Distribution</h4>
                <div class="space-y-3 pt-1">
                    <?php 
                    $totalBrowsers = array_sum($browserStats);
                    foreach ($browserStats as $brName => $brCount):
                        $brPercent = $totalBrowsers > 0 ? round(($brCount / $totalBrowsers) * 100) : 0;
                    ?>
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-center text-[10px]">
                                <span class="font-bold text-gray-400"><?= htmlspecialchars($brName) ?></span>
                                <span class="text-cyan-400 font-bold"><?= $brCount ?> Sessions (<?= $brPercent ?>%)</span>
                            </div>
                            <div class="w-full bg-gray-900 border border-gray-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-purple-500 h-full rounded-full" style="width: <?= $brPercent ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Enterprise Sessions Directory -->
    <div class="cyber-card p-6 space-y-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-cyan-500/10 pb-4 gap-4">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-white font-mono flex items-center gap-1.5">
                    <i data-lucide="shield-check" class="text-cyan-400 w-4.5 h-4.5"></i>
                    <span>Active Enterprise Sessions Directory</span>
                </h3>
                <span class="text-[10px] text-gray-500 mt-1 block">SIEM Global user session monitoring database ledger.</span>
            </div>
            <!-- Search Filter Input -->
            <input type="text" id="sessionSearch" placeholder="Search by operator node..." class="cyber-input py-1.5 px-3 max-w-xs text-xs font-mono" onkeyup="filterSessionsTable()" />
        </div>

        <div class="overflow-x-auto w-full pt-2">
            <table class="w-full text-left font-mono text-xs border-collapse">
                <thead>
                    <tr class="border-b border-gray-900 text-gray-400 text-[10px] uppercase font-bold">
                        <th class="pb-3 pl-2">Operator Node</th>
                        <th class="pb-3">Platform / User Agent</th>
                        <th class="pb-3">IP Address</th>
                        <th class="pb-3">Login Method</th>
                        <th class="pb-3 text-center">Rotations</th>
                        <th class="pb-3">Last Activity</th>
                        <th class="pb-3 text-right pr-2">Revocation</th>
                    </tr>
                </thead>
                <tbody id="sessionsTableBody">
                    <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500">No active sessions indexed in the system.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $s): ?>
                            <?php $isCurrent = ($s['id'] === $currentSessionId); ?>
                            <tr class="border-b border-gray-950/70 hover:bg-gray-950/30 transition-all duration-150 text-gray-300 session-row">
                                <td class="py-4 pl-2 font-bold text-white">
                                    <div class="flex flex-col">
                                        <span><?= htmlspecialchars($s['username'] ?? 'ANONYMOUS NODE') ?></span>
                                        <span class="text-[9px] text-gray-500 font-normal"><?= htmlspecialchars($s['email'] ?? '') ?></span>
                                    </div>
                                </td>
                                <td class="py-4">
                                    <div class="flex items-center gap-2">
                                        <?php if ($s['device'] === 'Mobile'): ?>
                                            <i data-lucide="smartphone" class="w-3.5 h-3.5 text-cyan-400 shrink-0"></i>
                                        <?php elseif ($s['device'] === 'Tablet'): ?>
                                            <i data-lucide="tablet" class="w-3.5 h-3.5 text-cyan-400 shrink-0"></i>
                                        <?php else: ?>
                                            <i data-lucide="monitor" class="w-3.5 h-3.5 text-cyan-400 shrink-0"></i>
                                        <?php endif; ?>
                                        <div class="flex flex-col truncate max-w-[200px]" title="<?= htmlspecialchars($s['user_agent']) ?>">
                                            <span><?= htmlspecialchars($s['os']) ?> &bull; <?= htmlspecialchars($s['browser']) ?></span>
                                            <span class="text-[8px] text-gray-500 truncate"><?= htmlspecialchars($s['user_agent']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 font-mono font-bold text-cyan-400/90"><?= htmlspecialchars($s['ip_address']) ?></td>
                                <td class="py-4">
                                    <span class="px-2 py-0.5 rounded text-[8px] font-bold tracking-wide uppercase border <?= $s['login_method'] === 'remember_me' ? 'bg-purple-500/15 text-purple-400 border-purple-500/30' : 'bg-cyan-500/15 text-cyan-400 border-cyan-500/30' ?>">
                                        <?= $s['login_method'] === 'remember_me' ? 'Remember Me Cookie' : 'Credentials Auth' ?>
                                    </span>
                                </td>
                                <td class="py-4 text-center font-bold text-white">
                                    <div class="inline-flex items-center gap-1.5" title="Session rotation counter">
                                        <i data-lucide="refresh-cw" class="w-3 h-3 text-purple-400 animate-spin-slow"></i>
                                        <span><?= $s['rotation_count'] ?></span>
                                    </div>
                                </td>
                                <td class="py-4 text-gray-400 font-normal"><?= date('Y-m-d H:i:s', $s['last_activity']) ?></td>
                                <td class="py-4 text-right pr-2">
                                    <?php if ($isCurrent): ?>
                                        <span class="px-2.5 py-0.5 rounded text-[8px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 inline-flex items-center gap-1">
                                            <span class="w-1 h-1 rounded-full bg-emerald-400 animate-ping"></span>
                                            <span>SELF ADMIN</span>
                                        </span>
                                    <?php else: ?>
                                        <button type="button" onclick="revokeSessionGlobal('<?= $s['id'] ?>', '<?= htmlspecialchars($s['username'] ?? 'Anonymous') ?>')" class="py-1 px-2.5 bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white rounded border border-rose-500/30 hover:border-rose-500 transition-all font-mono text-[9px] font-bold uppercase cursor-pointer">
                                            Revoke
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Session Rotation History and SIEM Audit Timelines -->
    <div class="cyber-card p-6 space-y-4">
        <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-2 flex items-center justify-between border-b border-cyan-500/10 pb-3">
            <span class="flex items-center gap-1.5">
                <i data-lucide="history" class="text-cyan-400 w-4.5 h-4.5"></i>
                <span>Chronological Session Rotation & Audit Log History</span>
            </span>
            <i data-lucide="refresh-cw" class="w-4 h-4 text-cyan-500/50"></i>
        </h3>

        <div class="relative border-l border-cyan-500/20 pl-4 ml-2 space-y-4 py-2 max-h-[300px] overflow-y-auto pr-1">
            <?php if (empty($sessionHistory)): ?>
                <div class="text-center py-6 text-gray-500">No session events indexed in this audit ledger cycle.</div>
            <?php else: ?>
                <?php foreach ($sessionHistory as $hist): ?>
                    <div class="relative space-y-1">
                        <!-- Dot indicator on timeline -->
                        <span class="absolute -left-[21px] top-1.5 w-2 h-2 rounded-full border border-gray-950 shadow-inner bg-cyan-500"></span>
                        
                        <div class="flex justify-between items-start">
                            <span class="text-white text-xs font-bold uppercase tracking-wide"><?= htmlspecialchars(str_replace('_', ' ', $hist['action'])) ?></span>
                            <span class="text-[9px] text-gray-500 font-mono"><?= date('Y-m-d H:i:s', strtotime($hist['created_at'])) ?></span>
                        </div>
                        <div class="text-[10px] text-gray-400">
                            <span>Operator: <strong class="text-cyan-400 font-bold"><?= htmlspecialchars($hist['username'] ?? 'Anonymous System') ?></strong></span>
                            <span class="mx-1.5 text-gray-700">|</span>
                            <span>IP Address: <?= htmlspecialchars($hist['ip_address']) ?></span>
                            <span class="mx-1.5 text-gray-700">|</span>
                            <span class="text-[9px] text-gray-400/80 font-normal"><?= htmlspecialchars($hist['threat_details'] ?? 'Transaction validated.') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Filter sessions table dynamically
function filterSessionsTable() {
    const input = document.getElementById("sessionSearch");
    const filter = input.value.toLowerCase();
    const rows = document.getElementsByClassName("session-row");

    for (let i = 0; i < rows.length; i++) {
        const text = rows[i].textContent || rows[i].innerText;
        if (text.toLowerCase().indexOf(filter) > -1) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
}

// Revoke specific session ID globally
async function revokeSessionGlobal(sessionId, username) {
    if (!confirm(`Are you sure you want to terminate session of operator '${username}'? Remote browser connection will be instantly disconnected.`)) {
        return;
    }
    SpinnerManager.show();

    try {
        const formData = new FormData();
        formData.append('session_id', sessionId);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/admin/sessions/terminate', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            ToastManager.show(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            ToastManager.show(data.message || 'Session revocation failed.', 'error');
        }
    } catch(e) {
        ToastManager.show('Gateway communication failure.', 'error');
    } finally {
        SpinnerManager.hide();
    }
}

// Force Clear all sessions for user ID
async function terminateUserSessions(userId, username) {
    if (!confirm(`CRITICAL ACTION: Are you sure you want to force terminate ALL active login sessions for user node '${username}'?`)) {
        return;
    }
    SpinnerManager.show();

    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/admin/sessions/terminate-user', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            ToastManager.show(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            ToastManager.show(data.message || 'Operator session clearing failed.', 'error');
        }
    } catch(e) {
        ToastManager.show('Gateway communication failure.', 'error');
    } finally {
        SpinnerManager.hide();
    }
}
</script>
<style>
/* Slow spin animation for indicator */
.animate-spin-slow {
    animation: spin-slow 8s linear infinite;
}
@keyframes spin-slow {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
