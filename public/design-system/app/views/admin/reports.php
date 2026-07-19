<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}
?>
<div class="space-y-6 animate-fade-in print:space-y-4 print:p-0">
    <!-- Breadcrumb Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-4 border-b border-slate-800 gap-4 print:border-b-2 print:border-black print:pb-2" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase print:text-black print:text-lg">SIEM Threat Audit Reports</h1>
            <span class="text-xs print:text-black" style="color: var(--color-foreground-muted);">Compile, filter, print, and export cellular protocol intrusion logs</span>
        </div>
        <div class="flex gap-2 print:hidden">
            <button onclick="window.print()" class="btn-secondary text-xs py-1.5 px-3">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Print Report</span>
            </button>
            <a href="<?= APP_URL ?>/admin/reports/export-csv?severity=<?= urlencode($severity) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn-primary text-xs py-1.5 px-3">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Export CSV (Excel)</span>
            </a>
        </div>
    </div>

    <!-- Filters Panel (Hidden when printing!) -->
    <div class="cyber-card p-4 print:hidden">
        <form method="GET" action="<?= APP_URL ?>/admin/reports" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Severity Tier</label>
                <select name="severity" class="cyber-input py-1.5 text-xs">
                    <option value="">All Severities</option>
                    <option value="low" <?= $severity === 'low' ? 'selected' : '' ?>>Low (<30% Risk)</option>
                    <option value="medium" <?= $severity === 'medium' ? 'selected' : '' ?>>Medium (30-69% Risk)</option>
                    <option value="high" <?= $severity === 'high' ? 'selected' : '' ?>>High (70-89% Risk)</option>
                    <option value="critical" <?= $severity === 'critical' ? 'selected' : '' ?>>Critical (>=90% Risk)</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="cyber-input py-1.5 text-xs" />
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="cyber-input py-1.5 text-xs" />
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-secondary w-full justify-center text-xs py-2">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span>Compile Report</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Report Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 print:grid-cols-4 print:gap-2">
        <div class="cyber-card flex flex-col justify-between print:border print:border-black print:p-2 print:text-black">
            <span class="text-3xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Total Threats</span>
            <span class="text-2xl font-bold font-mono block mt-1"><?= count($logs) ?></span>
        </div>
        <div class="cyber-card flex flex-col justify-between print:border print:border-black print:p-2 print:text-black">
            <span class="text-3xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Mean Anomaly Risk</span>
            <span class="text-2xl font-bold font-mono block mt-1 <?= $avgRisk >= 70 ? 'text-rose-500' : ($avgRisk >= 30 ? 'text-amber-500' : 'text-emerald-400') ?>"><?= $avgRisk ?>%</span>
        </div>
        <div class="cyber-card flex flex-col justify-between print:border print:border-black print:p-2 print:text-black">
            <span class="text-3xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Critical Intrusion Count</span>
            <span class="text-2xl font-bold text-rose-500 font-mono block mt-1"><?= $critCount ?></span>
        </div>
        <div class="cyber-card flex flex-col justify-between print:border print:border-black print:p-2 print:text-black">
            <span class="text-3xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">High Severity Warnings</span>
            <span class="text-2xl font-bold text-amber-500 font-mono block mt-1"><?= $highCount ?></span>
        </div>
    </div>

    <!-- Trend line chart (Hidden in Print Mode) -->
    <div class="cyber-card p-6 print:hidden">
        <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-4 font-mono">Report Trend Timeline</h3>
        <div class="h-48">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <!-- Threat Logs List -->
    <div class="cyber-card p-6 print:border print:border-black print:p-2">
        <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-4 font-mono print:text-black print:text-xs">Compiled Incident Logs Ledger</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse print:text-black">
                <thead>
                    <tr class="border-b font-mono text-[10px] print:border-b-2 print:border-black print:text-black" style="border-color: var(--color-border); color: var(--color-primary);">
                        <th class="pb-2">Timestamp</th>
                        <th class="pb-2">Operator</th>
                        <th class="pb-2">IP Address</th>
                        <th class="pb-2">Action Event</th>
                        <th class="pb-2 text-center">AI Risk</th>
                        <th class="pb-2">Classification</th>
                        <th class="pb-2">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y print:divide-black" style="divide-color: var(--color-border);">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="py-4 text-center" style="color: var(--color-foreground-muted);">No logs match filter parameters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-800/10 transition-colors text-gray-300 print:text-black">
                                <td class="py-2.5 font-mono text-[10px]"><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                                <td class="py-2.5 font-bold font-mono text-white print:text-black"><?= htmlspecialchars($log['username'] ?? 'SYSTEM') ?></td>
                                <td class="py-2.5 font-mono"><?= htmlspecialchars($log['ip_address']) ?></td>
                                <td class="py-2.5 font-semibold text-white print:text-black"><?= htmlspecialchars($log['action']) ?></td>
                                <td class="py-2.5 text-center font-mono font-bold <?= $log['risk_score'] >= 70 ? 'text-rose-500' : ($log['risk_score'] >= 30 ? 'text-amber-500' : 'text-emerald-400') ?>"><?= $log['risk_score'] ?>%</td>
                                <td class="py-2.5 font-mono text-[11px]" style="color: var(--color-accent);"><?= htmlspecialchars($log['threat_classification']) ?></td>
                                <td class="py-2.5 text-[11px] leading-relaxed" style="color: var(--color-foreground-muted);"><?= htmlspecialchars($log['threat_details']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Print Styles override CSS -->
<style>
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
    .cyber-card {
        background-color: transparent !important;
        border: 1px solid black !important;
        box-shadow: none !important;
    }
    canvas, .print-hidden {
        display: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const trendData = <?= json_encode($trendStats) ?>;
    const labels = trendData.map(t => t.date);
    const counts = trendData.map(t => t.count);

    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length > 0 ? labels : ['No Activity'],
            datasets: [{
                label: 'Intrusions Count',
                data: counts.length > 0 ? counts : [0],
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.05)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
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
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
