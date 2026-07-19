<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}
?>
<div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-800" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase">SIEM Threat & Usage Analytics</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Statistical analytics and growth trend charts for system accounts, alerts, and encryption usage</span>
        </div>
        <a href="<?= APP_URL ?>/admin" class="btn-secondary text-xs py-1.5 px-3">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Return to Terminal</span>
        </a>
    </div>

    <!-- Active indicators row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="cyber-card flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold block uppercase tracking-wider mb-1" style="color: var(--color-foreground-muted);">Active Sessions</span>
                <span class="text-2xl font-bold text-white font-mono"><?= $totalSessions ?></span>
            </div>
            <div class="p-2 bg-slate-900 border border-slate-850 rounded text-cyan-400">
                <i data-lucide="monitor" class="w-4 h-4"></i>
            </div>
        </div>
        <div class="cyber-card flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold block uppercase tracking-wider mb-1" style="color: var(--color-foreground-muted);">Total Alarms</span>
                <span class="text-2xl font-bold text-white font-mono"><?= $totalAlerts ?></span>
            </div>
            <div class="p-2 bg-slate-900 border border-slate-850 rounded text-amber-500">
                <i data-lucide="shield-alert" class="w-4 h-4"></i>
            </div>
        </div>
        <div class="cyber-card flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold block uppercase tracking-wider mb-1" style="color: var(--color-foreground-muted);">System Health State</span>
                <span class="text-2xl font-bold text-emerald-400 font-mono">NOMINAL</span>
            </div>
            <div class="p-2 bg-slate-900 border border-slate-850 rounded text-emerald-400">
                <i data-lucide="activity" class="w-4 h-4 animate-pulse"></i>
            </div>
        </div>
    </div>

    <!-- Charts Grid Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 1. User Growth -->
        <div class="cyber-card p-5 space-y-3">
            <h3 class="text-2xs font-bold text-white font-mono uppercase tracking-wider">Operator Growth Timeline</h3>
            <div class="h-56">
                <canvas id="userGrowthChart"></canvas>
            </div>
        </div>

        <!-- 2. Threat Growth -->
        <div class="cyber-card p-5 space-y-3">
            <h3 class="text-2xs font-bold text-white font-mono uppercase tracking-wider">Threat Incident Growth Trend</h3>
            <div class="h-56">
                <canvas id="threatGrowthChart"></canvas>
            </div>
        </div>

        <!-- 3. Encryption Usage -->
        <div class="cyber-card p-5 space-y-3">
            <h3 class="text-2xs font-bold text-white font-mono uppercase tracking-wider">GSM Payload Encryption Volume</h3>
            <div class="h-56">
                <canvas id="encryptChart"></canvas>
            </div>
        </div>

        <!-- 4. Login Statistics -->
        <div class="cyber-card p-5 space-y-3">
            <h3 class="text-2xs font-bold text-white font-mono uppercase tracking-wider">Access Statistics (Successful vs Failed Logins)</h3>
            <div class="h-56">
                <canvas id="loginChart"></canvas>
            </div>
        </div>

        <!-- 5. Risk Score Distribution -->
        <div class="cyber-card p-5 space-y-3">
            <h3 class="text-2xs font-bold text-white font-mono uppercase tracking-wider">Heuristics Risk Index Distribution</h3>
            <div class="h-56 relative flex items-center justify-center">
                <canvas id="riskDistChart"></canvas>
            </div>
        </div>

        <!-- 6. Monthly Log Activity -->
        <div class="cyber-card p-5 space-y-3">
            <h3 class="text-2xs font-bold text-white font-mono uppercase tracking-wider">System Transactions (Monthly Logs Volume)</h3>
            <div class="h-56">
                <canvas id="monthlyActivityChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Styling Options
    const fontSettings = { family: 'Fira Code', size: 9 };
    const gridSettings = { color: 'rgba(255, 255, 255, 0.05)' };

    // 1. User Growth
    const userRaw = <?= json_encode($userGrowth) ?>;
    new Chart(document.getElementById('userGrowthChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: userRaw.map(u => u.date),
            datasets: [{
                data: userRaw.map(u => u.count),
                borderColor: '#06b6d4',
                backgroundColor: 'rgba(6, 182, 212, 0.05)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { grid: gridSettings, ticks: { color: '#888888', font: fontSettings } },
                x: { grid: { display: false }, ticks: { color: '#888888', font: fontSettings } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // 2. Threat Growth
    const threatRaw = <?= json_encode($threatGrowth) ?>;
    new Chart(document.getElementById('threatGrowthChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: threatRaw.map(t => t.date),
            datasets: [{
                data: threatRaw.map(t => t.count),
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
                y: { grid: gridSettings, ticks: { color: '#888888', font: fontSettings } },
                x: { grid: { display: false }, ticks: { color: '#888888', font: fontSettings } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // 3. Encryption Usage
    const encRaw = <?= json_encode($encryptionUsage) ?>;
    new Chart(document.getElementById('encryptChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: encRaw.map(e => e.date),
            datasets: [{
                data: encRaw.map(e => e.count),
                backgroundColor: 'rgba(6, 182, 212, 0.4)',
                borderColor: '#06b6d4',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { grid: gridSettings, ticks: { color: '#888888', font: fontSettings } },
                x: { grid: { display: false }, ticks: { color: '#888888', font: fontSettings } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // 4. Login Statistics
    const successLogins = <?= json_encode($loginSuccess) ?>;
    const failedLogins = <?= json_encode($loginFailed) ?>;
    
    // Merge dates
    const loginDates = Array.from(new Set([...successLogins.map(s => s.date), ...failedLogins.map(f => f.date)])).sort();
    const successData = loginDates.map(d => {
        const found = successLogins.find(s => s.date === d);
        return found ? found.count : 0;
    });
    const failedData = loginDates.map(d => {
        const found = failedLogins.find(f => f.date === d);
        return found ? found.count : 0;
    });

    new Chart(document.getElementById('loginChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: loginDates,
            datasets: [
                {
                    label: 'Successful Access',
                    data: successData,
                    backgroundColor: 'rgba(16, 185, 129, 0.4)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Failed Access',
                    data: failedData,
                    backgroundColor: 'rgba(239, 68, 68, 0.4)',
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { grid: gridSettings, ticks: { color: '#888888', font: fontSettings } },
                x: { grid: { display: false }, ticks: { color: '#888888', font: fontSettings } }
            },
            plugins: {
                legend: {
                    labels: { color: '#888888', font: fontSettings }
                }
            }
        }
    });

    // 5. Risk Score Distribution
    new Chart(document.getElementById('riskDistChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Low Risk (<30%)', 'Medium Risk (30-69%)', 'Critical (>=70%)'],
            datasets: [{
                data: [<?= $riskLow ?>, <?= $riskMed ?>, <?= $riskHigh ?>],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.65)',
                    'rgba(245, 158, 11, 0.65)',
                    'rgba(239, 68, 68, 0.65)'
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
                    labels: { color: '#888888', font: fontSettings }
                }
            }
        }
    });

    // 6. Monthly Log Activity
    const monthlyRaw = <?= json_encode($monthlyActivity) ?>;
    new Chart(document.getElementById('monthlyActivityChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: monthlyRaw.map(m => m.month),
            datasets: [{
                data: monthlyRaw.map(m => m.count),
                backgroundColor: 'rgba(139, 92, 246, 0.4)',
                borderColor: '#8b5cf6',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { grid: gridSettings, ticks: { color: '#888888', font: fontSettings } },
                x: { grid: { display: false }, ticks: { color: '#888888', font: fontSettings } }
            },
            plugins: { legend: { display: false } }
        }
    });
});
</script>
