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
            <h1 class="text-xl font-bold text-white font-mono uppercase print:text-black print:text-lg">Compliance Audit Trail</h1>
            <span class="text-xs print:text-black" style="color: var(--color-foreground-muted);">Inspect low-level database operations, tracing data lifecycle adjustments</span>
        </div>
        <div class="flex gap-2 print:hidden font-mono text-xs">
            <button onclick="window.print()" class="btn-secondary py-1.5 px-3">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Print Trail</span>
            </button>
            <a href="<?= APP_URL ?>/admin/audit/export?search=<?= urlencode($search) ?>&action_type=<?= urlencode($actionType) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&sort_by=<?= urlencode($sortBy) ?>&sort_order=<?= urlencode($sortOrder) ?>" class="btn-primary py-1.5 px-3">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    <!-- Parameter Filters Workspace -->
    <div class="cyber-card p-4 print:hidden">
        <form method="GET" action="<?= APP_URL ?>/admin/audit" class="grid grid-cols-1 sm:grid-cols-5 gap-4">
            <!-- Text Search -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Search Keywords</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search tables, users..." class="cyber-input py-1.5 text-xs" />
            </div>

            <!-- Action Type Filter -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Operation Type</label>
                <select name="action_type" class="cyber-input py-1.5 text-xs font-mono">
                    <option value="">All Operations</option>
                    <option value="INSERT" <?= $actionType === 'INSERT' ? 'selected' : '' ?>>INSERT</option>
                    <option value="UPDATE" <?= $actionType === 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                    <option value="DELETE" <?= $actionType === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
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

            <!-- Sorting Parameters -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Sort By</label>
                    <select name="sort_by" class="cyber-input py-1.5 text-2xs">
                        <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Timestamp</option>
                        <option value="table_name" <?= $sortBy === 'table_name' ? 'selected' : '' ?>>Table</option>
                        <option value="action_type" <?= $sortBy === 'action_type' ? 'selected' : '' ?>>Operation</option>
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
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Apply Filter Constraints</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Active Audit Trend Visualizer (Hidden when printing!) -->
    <div class="cyber-card p-6 print:hidden">
        <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-4 font-mono">Compliance Modifications Activity Trend</h3>
        <div class="h-44">
            <canvas id="auditTrendChart"></canvas>
        </div>
    </div>

    <!-- Auditing Ledger Table -->
    <div class="cyber-card p-6 print:border print:border-black print:p-2">
        <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-4 font-mono print:text-black">
            <span>Audit Ledger Database transactions (Found: <?= $totalRows ?> records)</span>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse print:text-black">
                <thead>
                    <tr class="border-b font-mono text-[10px] print:border-b-2 print:border-black print:text-black" style="border-color: var(--color-border); color: var(--color-primary);">
                        <th class="pb-2.5">Timestamp</th>
                        <th class="pb-2.5">Operator</th>
                        <th class="pb-2.5">Operation</th>
                        <th class="pb-2.5">Target Table</th>
                        <th class="pb-2.5">Record ID</th>
                        <th class="pb-2.5 font-mono">IP Address</th>
                        <th class="pb-2.5">Session ID</th>
                        <th class="pb-2.5 text-right">Details Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y print:divide-black" style="divide-color: var(--color-border);">
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="8" class="py-4 text-center" style="color: var(--color-foreground-muted);">No records registered in the compliance ledger under current parameters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr class="hover:bg-slate-800/10 transition-colors text-gray-300 print:text-black">
                                <td class="py-3 font-mono text-[10px]"><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                                <td class="py-3 font-bold font-mono text-white print:text-black"><?= htmlspecialchars($row['operator'] ?? 'SYSTEM') ?></td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 rounded text-[8px] font-bold font-mono tracking-wider <?= $row['action_type'] === 'INSERT' ? 'bg-emerald-500/10 text-emerald-400' : ($row['action_type'] === 'UPDATE' ? 'bg-cyan-500/10 text-cyan-400' : 'bg-red-500/10 text-red-400') ?>">
                                        <?= htmlspecialchars($row['action_type']) ?>
                                    </span>
                                </td>
                                <td class="py-3 font-mono text-[11px]" style="color: var(--color-accent);"><?= htmlspecialchars($row['table_name']) ?></td>
                                <td class="py-3 font-mono"><?= $row['record_id'] ?></td>
                                <td class="py-3 font-mono" style="color: var(--color-foreground-muted);"><?= htmlspecialchars($row['ip_address']) ?></td>
                                <td class="py-3 font-mono text-[10px]" style="color: var(--color-foreground-muted);"><?= substr($row['session_id'], 0, 12) ?>...</td>
                                <td class="py-3 text-right">
                                    <button onclick="inspectValues('<?= htmlspecialchars(json_encode($row['old_values'])) ?>', '<?= htmlspecialchars(json_encode($row['new_values'])) ?>')" 
                                            class="py-1 px-2 border border-slate-700 hover:bg-slate-800 text-gray-300 rounded text-[10px] font-bold cursor-pointer font-mono print:hidden">
                                        Inspect Values
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-between items-center mt-6 pt-4 border-t print:hidden" style="border-color: var(--color-border); font-family: var(--font-family-mono); font-size: 11px; color: var(--color-foreground-muted);">
                <span>Showing page <?= $page ?> of <?= $totalPages ?> (Total: <?= $totalRows ?> records)</span>
                <div class="flex gap-2">
                    <a href="?search=<?= urlencode($search) ?>&action_type=<?= urlencode($actionType) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&sort_by=<?= urlencode($sortBy) ?>&sort_order=<?= urlencode($sortOrder) ?>&page=<?= max(1, $page - 1) ?>" 
                       class="py-1 px-3 border border-slate-800 hover:border-cyan-500 rounded transition-all <?= $page === 1 ? 'pointer-events-none opacity-40' : '' ?>">Prev</a>
                    <a href="?search=<?= urlencode($search) ?>&action_type=<?= urlencode($actionType) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&sort_by=<?= urlencode($sortBy) ?>&sort_order=<?= urlencode($sortOrder) ?>&page=<?= min($totalPages, $page + 1) ?>" 
                       class="py-1 px-3 border border-slate-800 hover:border-cyan-500 rounded transition-all <?= $page === $totalPages ? 'pointer-events-none opacity-40' : '' ?>">Next</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Inspect Database Changes -->
<div id="inspectModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="w-full max-w-2xl p-6 rounded-2xl border" style="background-color: var(--color-surface); border-color: var(--color-border);">
        <h3 class="text-sm font-bold text-white font-mono uppercase mb-4 border-b pb-2" style="border-color: var(--color-border);">Lifecycle State Alteration Inspection</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-2xs font-mono">
            <div>
                <span class="block font-bold text-red-400 mb-1.5 uppercase">Pre-Operation Values (OLD)</span>
                <pre id="oldValArea" class="p-3 bg-slate-900 border border-slate-800 rounded-xl overflow-x-auto max-h-[300px] text-gray-300"></pre>
            </div>
            <div>
                <span class="block font-bold text-emerald-400 mb-1.5 uppercase">Post-Operation Values (NEW)</span>
                <pre id="newValArea" class="p-3 bg-slate-900 border border-slate-800 rounded-xl overflow-x-auto max-h-[300px] text-gray-300"></pre>
            </div>
        </div>

        <div class="flex justify-end pt-4 mt-4 border-t" style="border-color: var(--color-border);">
            <button onclick="closeInspect()" class="btn-secondary text-[10px] py-1.5 px-3 cursor-pointer">Close</button>
        </div>
    </div>
</div>

<script>
function inspectValues(oldVal, newVal) {
    // Parse escaped double json encoding if required
    let oldJson = {};
    let newJson = {};
    try {
        oldJson = JSON.parse(oldVal ? JSON.parse(oldVal) : '{}');
    } catch(e) {
        oldJson = oldVal ? JSON.parse(oldVal) : {};
    }
    try {
        newJson = JSON.parse(newVal ? JSON.parse(newVal) : '{}');
    } catch(e) {
        newJson = newVal ? JSON.parse(newVal) : {};
    }

    document.getElementById('oldValArea').textContent = JSON.stringify(oldJson, null, 4);
    document.getElementById('newValArea').textContent = JSON.stringify(newJson, null, 4);
    document.getElementById('inspectModal').classList.remove('hidden');
}

function closeInspect() {
    document.getElementById('inspectModal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    const trendData = <?= json_encode($chartData) ?>;
    const labels = trendData.map(t => t.date);
    const counts = trendData.map(t => t.count);

    const ctx = document.getElementById('auditTrendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length > 0 ? labels : ['No Operations Logged'],
            datasets: [{
                label: 'Modifications Count',
                data: counts.length > 0 ? counts : [0],
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.05)',
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
