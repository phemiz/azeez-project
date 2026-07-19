<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}
?>
<div class="space-y-6 animate-fade-in print:space-y-4 print:p-0">
    <!-- Header Block -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center pb-4 border-b border-slate-800 gap-4 print:border-b-2 print:border-black print:pb-2" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase print:text-black print:text-lg">SIEM Centralized Reports Engine</h1>
            <span class="text-xs print:text-black" style="color: var(--color-foreground-muted);">Deploy modular audits across all cellular databases and session transactions</span>
        </div>
        <div class="flex gap-2 print:hidden font-mono text-xs">
            <button onclick="window.print()" class="btn-secondary py-1.5 px-3">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Print View</span>
            </button>
            <a href="<?= APP_URL ?>/admin/reports/export?report_type=<?= urlencode($reportType) ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&sort_by=<?= urlencode($sortBy) ?>&sort_order=<?= urlencode($sortOrder) ?>&format=csv" class="btn-secondary py-1.5 px-3">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>CSV</span>
            </a>
            <a href="<?= APP_URL ?>/admin/reports/export?report_type=<?= urlencode($reportType) ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&sort_by=<?= urlencode($sortBy) ?>&sort_order=<?= urlencode($sortOrder) ?>&format=excel" class="btn-secondary py-1.5 px-3">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                <span>Excel</span>
            </a>
            <a href="<?= APP_URL ?>/admin/reports/export?report_type=<?= urlencode($reportType) ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&sort_by=<?= urlencode($sortBy) ?>&sort_order=<?= urlencode($sortOrder) ?>&format=pdf" target="_blank" class="btn-primary py-1.5 px-3">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>PDF Print</span>
            </a>
        </div>
    </div>

    <!-- Parameter Filters Workspace -->
    <div class="cyber-card p-4 print:hidden">
        <form method="GET" action="<?= APP_URL ?>/admin/reports/central" class="grid grid-cols-1 sm:grid-cols-5 gap-4">
            <!-- Report Category Selector -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Audit Category</label>
                <select name="report_type" class="cyber-input py-1.5 text-xs font-mono">
                    <option value="users" <?= $reportType === 'users' ? 'selected' : '' ?>>User Reports</option>
                    <option value="security" <?= $reportType === 'security' ? 'selected' : '' ?>>Security Reports</option>
                    <option value="threats" <?= $reportType === 'threats' ? 'selected' : '' ?>>Threat Reports</option>
                    <option value="logins" <?= $reportType === 'logins' ? 'selected' : '' ?>>Login Reports</option>
                    <option value="encryptions" <?= $reportType === 'encryptions' ? 'selected' : '' ?>>Encryption Reports</option>
                    <option value="otp" <?= $reportType === 'otp' ? 'selected' : '' ?>>OTP Reports</option>
                    <option value="ai" <?= $reportType === 'ai' ? 'selected' : '' ?>>AI Reports</option>
                    <option value="activity" <?= $reportType === 'activity' ? 'selected' : '' ?>>Activity Reports</option>
                    <option value="audit" <?= $reportType === 'audit' ? 'selected' : '' ?>>Audit Reports</option>
                    <option value="system" <?= $reportType === 'system' ? 'selected' : '' ?>>System Reports</option>
                </select>
            </div>

            <!-- Date Constraints -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="cyber-input py-1.5 text-xs" />
            </div>
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="cyber-input py-1.5 text-xs" />
            </div>

            <!-- Search Ledger -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Text Query</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Filter matches..." class="cyber-input py-1.5 text-xs" />
            </div>

            <!-- Sorting Parameters -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Sort By</label>
                    <select name="sort_by" class="cyber-input py-1.5 text-2xs">
                        <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Date</option>
                        <option value="id" <?= $sortBy === 'id' ? 'selected' : '' ?>>ID</option>
                        <option value="risk_score" <?= $sortBy === 'risk_score' ? 'selected' : '' ?>>Risk</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Order</label>
                    <select name="sort_order" class="cyber-input py-1.5 text-2xs">
                        <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>DESC</option>
                        <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>ASC</option>
                    </select>
                </div>
            </div>

            <!-- Action Button -->
            <div class="sm:col-span-5 flex justify-end mt-2">
                <button type="submit" class="btn-primary text-xs py-2 px-6">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    <span>Generate Workspace Report</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Active Report Trend Visualizer (Hidden when printing!) -->
    <div class="cyber-card p-6 print:hidden">
        <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-4 font-mono">Report Category Activity Trend</h3>
        <div class="h-44">
            <canvas id="reportsTrendChart"></canvas>
        </div>
    </div>

    <!-- Reusable Dynamic Table layout -->
    <div class="cyber-card p-6 print:border print:border-black print:p-2">
        <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-4 font-mono print:text-black">
            <span>Report Category ledger results (Found: <?= $totalRows ?> matches)</span>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse print:text-black">
                <thead>
                    <tr class="border-b font-mono text-[10px] print:border-b-2 print:border-black print:text-black" style="border-color: var(--color-border); color: var(--color-primary);">
                        <?php if (!empty($rows)): ?>
                            <?php foreach (array_keys($rows[0]) as $col): ?>
                                <th class="pb-2.5 uppercase"><?= htmlspecialchars(str_replace('_', ' ', $col)) ?></th>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <th class="pb-2.5">Records</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y print:divide-black" style="divide-color: var(--color-border);">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td class="py-4 text-center" style="color: var(--color-foreground-muted);">No entries complied under current filter parameters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr class="hover:bg-slate-800/10 transition-colors text-gray-300 print:text-black">
                                <?php foreach ($row as $key => $val): ?>
                                    <td class="py-3 font-mono text-[11px]">
                                        <?php if ($key === 'status'): ?>
                                            <span class="px-2 py-0.5 rounded text-[8px] font-bold <?= $val === 'active' || $val === 'open' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' ?>">
                                                <?= htmlspecialchars(strtoupper($val)) ?>
                                            </span>
                                        <?php elseif ($key === 'risk_score'): ?>
                                            <span class="font-bold <?= $val >= 70 ? 'text-rose-500 font-bold' : ($val >= 30 ? 'text-amber-500' : 'text-emerald-400') ?>"><?= $val ?>%</span>
                                        <?php else: ?>
                                            <?= htmlspecialchars($val ?? 'N/A') ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Centralized Paginator -->
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-between items-center mt-6 pt-4 border-t print:hidden" style="border-color: var(--color-border); font-family: var(--font-family-mono); font-size: 11px; color: var(--color-foreground-muted);">
                <span>Showing page <?= $page ?> of <?= $totalPages ?> (Total matches: <?= $totalRows ?>)</span>
                <div class="flex gap-2">
                    <a href="?report_type=<?= urlencode($reportType) ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&sort_by=<?= urlencode($sortBy) ?>&sort_order=<?= urlencode($sortOrder) ?>&page=<?= max(1, $page - 1) ?>" 
                       class="py-1 px-3 border border-slate-800 hover:border-cyan-500 rounded transition-all <?= $page === 1 ? 'pointer-events-none opacity-40' : '' ?>">Prev</a>
                    <a href="?report_type=<?= urlencode($reportType) ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&sort_by=<?= urlencode($sortBy) ?>&sort_order=<?= urlencode($sortOrder) ?>&page=<?= min($totalPages, $page + 1) ?>" 
                       class="py-1 px-3 border border-slate-800 hover:border-cyan-500 rounded transition-all <?= $page === $totalPages ? 'pointer-events-none opacity-40' : '' ?>">Next</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Print media CSS rules for full printable sheets -->
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
    canvas, .print:hidden {
        display: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const trendData = <?= json_encode($trendData) ?>;
    const labels = trendData.map(t => t.date);
    const counts = trendData.map(t => t.count);

    const ctx = document.getElementById('reportsTrendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length > 0 ? labels : ['No Activity Logged'],
            datasets: [{
                label: 'Activity Volume',
                data: counts.length > 0 ? counts : [0],
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
