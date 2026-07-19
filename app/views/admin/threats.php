<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}
?>
<div class="space-y-8 animate-fade-in">
    <!-- Breadcrumb Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center pb-4 border-b border-slate-800 gap-4" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase">AI Threat Intel & SIEM Center</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Statistical mapping of cellular and protocol intrusion threat signals</span>
        </div>
        <div class="flex gap-3">
            <a href="<?= APP_URL ?>/admin" class="btn-secondary text-xs py-1.5 px-3">
                <i data-lucide="shield" class="w-4 h-4"></i>
                <span>Admin Panel</span>
            </a>
            <a href="<?= APP_URL ?>/dashboard" class="btn-secondary text-xs py-1.5 px-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>User Portal</span>
            </a>
        </div>
    </div>

    <!-- AI Threat Metrics Row -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6">
        <!-- Global Security Score -->
        <div class="cyber-card flex items-center justify-between">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Global Security Score</span>
                <span class="text-3xl font-bold font-mono block mt-1" style="color: var(--color-primary);"><?= $globalScore ?>/100</span>
            </div>
            <div class="p-3 bg-cyan-500/5 rounded-lg border border-cyan-500/20">
                <i data-lucide="shield-check" class="w-6 h-6" style="color: var(--color-primary);"></i>
            </div>
        </div>

        <!-- Total Threats -->
        <div class="cyber-card flex items-center justify-between">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Total Threat Signals</span>
                <span class="text-3xl font-bold font-mono block mt-1" style="color: var(--color-primary);"><?= $totalThreats ?></span>
            </div>
            <div class="p-3 bg-cyan-500/5 rounded-lg border border-cyan-500/20">
                <i data-lucide="shield-alert" class="w-6 h-6" style="color: var(--color-primary);"></i>
            </div>
        </div>

        <!-- Critical Severity -->
        <div class="cyber-card flex items-center justify-between">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Critical Intrusion Alerts</span>
                <span class="text-3xl font-bold font-mono block mt-1" style="color: var(--color-accent);"><?= $criticalThreats ?></span>
            </div>
            <div class="p-3 bg-red-500/5 rounded-lg border border-red-500/20">
                <i data-lucide="flame" class="w-6 h-6" style="color: var(--color-accent);"></i>
            </div>
        </div>

        <!-- Medium Warnings -->
        <div class="cyber-card flex items-center justify-between">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Medium Warnings</span>
                <span class="text-3xl font-bold font-mono block mt-1 text-amber-500"><?= $mediumThreats ?></span>
            </div>
            <div class="p-3 bg-amber-500/5 rounded-lg border border-amber-500/20">
                <i data-lucide="alert-triangle" class="w-6 h-6 text-amber-500"></i>
            </div>
        </div>
    </div>

    <!-- Threat Analytics Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Daily Threat Trends -->
        <div class="cyber-card lg:col-span-2 p-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white mb-4 font-mono">7-Day Threat Activity Trend</h3>
            <div class="h-64">
                <canvas id="dailyThreatsChart"></canvas>
            </div>
        </div>

        <!-- Threat Distribution Categories -->
        <div class="cyber-card p-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white mb-4 font-mono">Anomaly Classification</h3>
            <div class="h-64 relative flex items-center justify-center">
                <canvas id="threatDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- High Risk Users & AI Recommendations Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- High Risk Users -->
        <div class="cyber-card lg:col-span-2 p-6 space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white font-mono flex items-center justify-between">
                <span>High Risk Operator Nodes</span>
                <i data-lucide="users" class="w-4 h-4 text-cyan-400"></i>
            </h3>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b font-mono text-[10px]" style="border-color: var(--color-border); color: var(--color-primary);">
                            <th class="pb-2">Operator</th>
                            <th class="pb-2">Secure Email</th>
                            <th class="pb-2 text-center">Triggered Threats</th>
                            <th class="pb-2 text-center">Peak Risk Rating</th>
                            <th class="pb-2">Recommended Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="divide-color: var(--color-border);">
                        <?php if (empty($highRiskUsers)): ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center" style="color: var(--color-foreground-muted);">No operator accounts flagged.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($highRiskUsers as $hu): ?>
                                <tr class="hover:bg-slate-800/10 transition-colors">
                                    <td class="py-3 font-bold text-white font-mono"><?= htmlspecialchars($hu['username']) ?></td>
                                    <td class="py-3" style="color: var(--color-foreground-muted);"><?= htmlspecialchars($hu['email']) ?></td>
                                    <td class="py-3 text-center text-white font-mono"><?= $hu['threat_count'] ?></td>
                                    <td class="py-3 text-center text-rose-500 font-mono font-bold"><?= $hu['peak_risk'] ?>%</td>
                                    <td class="py-3">
                                        <?php if ($hu['peak_risk'] >= 70): ?>
                                            <span class="text-rose-500 font-bold uppercase text-[10px]">Suspend Active Node</span>
                                        <?php else: ?>
                                            <span class="text-amber-500 font-bold uppercase text-[10px]">Force OTP Verification</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- AI Recommendations -->
        <div class="cyber-card p-6 space-y-4" style="background-color: var(--color-surface);">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white font-mono flex items-center gap-1.5 border-b pb-3" style="border-color: var(--color-border);">
                <i data-lucide="brain-circuit" class="w-4 h-4" style="color: var(--color-primary);"></i>
                <span>SIEM Hardening Rules</span>
            </h3>
            
            <div class="space-y-3 text-xs font-mono">
                <?php if ($criticalThreats > 0): ?>
                    <div class="p-3 rounded-lg border border-red-500/20 bg-red-500/5 text-rose-500">
                        <strong>CRITICAL:</strong> Suspend session credentials showing risk above 70% immediately to prevent potential exfiltrations.
                    </div>
                <?php endif; ?>
                <?php if ($totalThreats > 5): ?>
                    <div class="p-3 rounded-lg border border-amber-500/20 bg-amber-500/5 text-amber-500">
                        <strong>HIGH:</strong> Enable sliding IP rate-limiting filters against multiple brute force request sources.
                    </div>
                <?php endif; ?>
                <div class="p-3 rounded-lg border border-cyan-500/20 bg-cyan-500/5 text-cyan-400">
                    <strong>BASELINE:</strong> Restrict bulk decrypt output exports. Mandate 2FA validation challenges on credentials changes.
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Threat Events Table -->
    <div class="cyber-card p-6 space-y-4">
        <h3 class="text-xs font-bold uppercase tracking-wider text-white flex items-center justify-between font-mono">
            <span>Threat Auditing Ledger (Last 20 Incidents)</span>
            <i data-lucide="server-crash" class="w-4 h-4" style="color: var(--color-accent);"></i>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse" style="color: var(--color-foreground);">
                <thead>
                    <tr class="border-b font-mono text-[10px]" style="border-color: var(--color-border); color: var(--color-primary);">
                        <th class="pb-2.5 font-medium">Timestamp</th>
                        <th class="pb-2.5 font-medium">Operator Node</th>
                        <th class="pb-2.5 font-medium">IP Address</th>
                        <th class="pb-2.5 font-medium">Action Event</th>
                        <th class="pb-2.5 font-medium text-center">Evaluated Risk</th>
                        <th class="pb-2.5 font-medium">Classification Target</th>
                        <th class="pb-2.5 font-medium">Mitigation Actions Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="divide-color: var(--color-border);">
                    <?php if (empty($recentThreatEvents)): ?>
                        <tr>
                            <td colspan="7" class="py-4 text-center" style="color: var(--color-foreground-muted);">No threat anomalies cataloged. System healthy.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentThreatEvents as $event): ?>
                            <?php 
                                $riskColor = 'text-emerald-400';
                                if ($event['risk_score'] >= 70) $riskColor = 'text-rose-500 font-bold animate-pulse';
                                elseif ($event['risk_score'] >= 30) $riskColor = 'text-amber-500';
                            ?>
                            <tr class="hover:bg-slate-800/10 transition-colors">
                                <td class="py-3 font-mono text-[10px]" style="color: var(--color-foreground-muted);"><?= date('Y-m-d H:i', strtotime($event['created_at'])) ?></td>
                                <td class="py-3 font-bold text-white font-mono"><?= htmlspecialchars($event['username'] ?? 'SYSTEM') ?></td>
                                <td class="py-3 font-mono" style="color: var(--color-foreground-muted);"><?= htmlspecialchars($event['ip_address']) ?></td>
                                <td class="py-3 font-semibold" style="color: var(--color-foreground-title);"><?= htmlspecialchars($event['action']) ?></td>
                                <td class="py-3 text-center <?= $riskColor ?> font-mono font-bold"><?= $event['risk_score'] ?>%</td>
                                <td class="py-3 font-mono text-[11px]" style="color: var(--color-accent);"><?= htmlspecialchars($event['threat_classification']) ?></td>
                                <td class="py-3 text-[11px] leading-relaxed text-gray-300"><?= htmlspecialchars($event['threat_details'] ?: 'No mitigation triggers required.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Setup Daily Threats Trend Line Chart
    const dailyData = <?= json_encode($dailyThreats) ?>;
    const dailyLabels = dailyData.map(d => d.date);
    const dailyCounts = dailyData.map(d => d.count);

    const ctxDaily = document.getElementById('dailyThreatsChart').getContext('2d');
    new Chart(ctxDaily, {
        type: 'line',
        data: {
            labels: dailyLabels.length > 0 ? dailyLabels : ['No Threat Logged'],
            datasets: [{
                label: 'Threat Count',
                data: dailyCounts.length > 0 ? dailyCounts : [0],
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.05)',
                borderWidth: 2,
                tension: 0.35,
                fill: true,
                pointBackgroundColor: '#ef4444'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#888888', font: { family: 'Fira Code' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#888888', font: { family: 'Fira Code' } }
                }
            },
            plugins: { legend: { display: false } }
        }
    });

    // 2. Setup Threat Distribution Categories Doughnut Chart
    const classData = <?= json_encode($classificationStats) ?>;
    const classLabels = classData.map(c => c.threat_classification);
    const classCounts = classData.map(c => c.count);

    const ctxClass = document.getElementById('threatDistributionChart').getContext('2d');
    new Chart(ctxClass, {
        type: 'doughnut',
        data: {
            labels: classLabels.length > 0 ? classLabels : ['System Healthy'],
            datasets: [{
                data: classCounts.length > 0 ? classCounts : [1],
                backgroundColor: [
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(6, 182, 212, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(139, 92, 246, 0.7)'
                ],
                borderColor: '#0b0f19',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#888888', font: { family: 'Fira Code', size: 10 } }
                }
            }
        }
    });
});
</script>
