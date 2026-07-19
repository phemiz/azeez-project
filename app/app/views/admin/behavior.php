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
            <h1 class="text-xl font-bold text-white font-mono uppercase">Operator Behavior Analytics</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Establishing and inspecting behavioral baselines for active operators</span>
        </div>
        
        <!-- User Selection Dropdown -->
        <div class="flex items-center gap-3 font-mono text-xs">
            <span style="color: var(--color-foreground-muted);">Inspect Node:</span>
            <select onchange="window.location.href='<?= APP_URL ?>/admin/behavior?user_id=' + this.value" class="cyber-input py-1.5 px-3">
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $u['id'] == $targetUser['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Behavior metrics cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6">
        <!-- Anomaly Risk Score -->
        <div class="cyber-card flex items-center justify-between">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Behavior Risk rating</span>
                <span class="text-3xl font-bold font-mono block mt-1 <?= $behaviorProfile['behavior_risk'] >= 50 ? 'text-rose-500 font-bold' : ($behaviorProfile['behavior_risk'] >= 25 ? 'text-amber-500' : 'text-emerald-400') ?>">
                    <?= $behaviorProfile['behavior_risk'] ?>%
                </span>
            </div>
            <div class="p-3 bg-slate-900/40 rounded-lg border border-slate-800">
                <i data-lucide="activity" class="w-6 h-6" style="color: var(--color-primary);"></i>
            </div>
        </div>

        <!-- Mean Daily Encryption rate -->
        <div class="cyber-card flex items-center justify-between">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Mean Daily Envelopes</span>
                <span class="text-3xl font-bold font-mono block mt-1" style="color: var(--color-foreground-title);">
                    <?= $behaviorProfile['baselines']['mean_daily_encryptions'] ?>/day
                </span>
            </div>
            <div class="p-3 bg-slate-900/40 rounded-lg border border-slate-800">
                <i data-lucide="mail-search" class="w-6 h-6" style="color: var(--color-primary);"></i>
            </div>
        </div>

        <!-- Session duration baseline -->
        <div class="cyber-card flex items-center justify-between">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Session Duration Mean</span>
                <span class="text-3xl font-bold font-mono block mt-1" style="color: var(--color-foreground-title);">
                    <?= $behaviorProfile['baselines']['average_session_mins'] ?> min
                </span>
            </div>
            <div class="p-3 bg-slate-900/40 rounded-lg border border-slate-800">
                <i data-lucide="hourglass" class="w-6 h-6" style="color: var(--color-primary);"></i>
            </div>
        </div>

        <!-- Credential Volatility -->
        <div class="cyber-card flex items-center justify-between">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Password Resets (90d)</span>
                <span class="text-3xl font-bold font-mono block mt-1 <?= $behaviorProfile['baselines']['recent_pw_changes'] >= 3 ? 'text-rose-500' : 'text-slate-300' ?>">
                    <?= $behaviorProfile['baselines']['recent_pw_changes'] ?>
                </span>
            </div>
            <div class="p-3 bg-slate-900/40 rounded-lg border border-slate-800">
                <i data-lucide="refresh-cw" class="w-6 h-6" style="color: var(--color-primary);"></i>
            </div>
        </div>
    </div>

    <!-- Charts and Profiles Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Hourly login patterns distribution -->
        <div class="cyber-card lg:col-span-2 p-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white mb-4 font-mono">Diurnal Activity Profile (24-Hour Login Probability %)</h3>
            <div class="h-64">
                <canvas id="diurnalChart"></canvas>
            </div>
        </div>

        <!-- Right: Primary Configuration Baselines -->
        <div class="cyber-card p-6 space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-white font-mono border-b pb-3" style="border-color: var(--color-border);">Baseline Fingerprints</h3>
            
            <div class="space-y-4 text-xs font-mono">
                <div class="flex justify-between border-b pb-2" style="border-color: var(--color-border);">
                    <span style="color: var(--color-foreground-muted);">Operator Email:</span>
                    <span class="text-white"><?= htmlspecialchars($targetUser['email']) ?></span>
                </div>
                <div class="flex justify-between border-b pb-2" style="border-color: var(--color-border);">
                    <span style="color: var(--color-foreground-muted);">Primary OS Baseline:</span>
                    <span class="text-cyan-400 font-bold"><?= htmlspecialchars($behaviorProfile['baselines']['primary_os']) ?></span>
                </div>
                <div class="flex justify-between border-b pb-2" style="border-color: var(--color-border);">
                    <span style="color: var(--color-foreground-muted);">Primary Browser Baseline:</span>
                    <span class="text-cyan-400 font-bold"><?= htmlspecialchars($behaviorProfile['baselines']['primary_browser']) ?></span>
                </div>
                <div class="flex justify-between border-b pb-2" style="border-color: var(--color-border);">
                    <span style="color: var(--color-foreground-muted);">Time patterns state:</span>
                    <span class="text-white">Continuous daytime</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Anomalies and Mitigation recommendations -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Anomalies list -->
        <div class="cyber-card lg:col-span-2 p-6 space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-white font-mono flex items-center gap-1.5">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500"></i>
                <span>Flagged Behavioral Anomalies</span>
            </h3>

            <div class="space-y-3 font-mono text-xs">
                <?php if (empty($behaviorProfile['anomalies'])): ?>
                    <div class="p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/5 text-emerald-400">
                        Zero anomalies detected. Current node behavior matches historical baselines.
                    </div>
                <?php else: ?>
                    <?php foreach ($behaviorProfile['anomalies'] as $anom): ?>
                        <div class="p-3 rounded-xl border border-rose-500/20 bg-rose-500/5 text-rose-400 flex items-start gap-2">
                            <i data-lucide="shield-alert" class="w-4 h-4 mt-0.5"></i>
                            <span><?= htmlspecialchars($anom) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- AI Mitigation recommendations -->
        <div class="cyber-card p-6 space-y-4" style="background-color: var(--color-surface);">
            <h3 class="text-xs font-bold uppercase tracking-wider text-white font-mono flex items-center gap-1.5">
                <i data-lucide="brain-circuit" class="w-4 h-4" style="color: var(--color-primary);"></i>
                <span>Mitigation Rules Plan</span>
            </h3>

            <p class="text-xs leading-relaxed" style="color: var(--color-foreground);">
                <?= htmlspecialchars($behaviorProfile['recommendations']) ?>
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const diurnalData = <?= json_encode($behaviorProfile['baselines']['diurnal_distribution']) ?>;
    const hoursLabels = Array.from({ length: 24 }, (_, i) => `${i.toString().padStart(2, '0')}:00`);

    const ctx = document.getElementById('diurnalChart').getContext('2d');
    
    // Gradient fill setup
    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, 'rgba(6, 182, 212, 0.4)');
    gradient.addColorStop(1, 'rgba(6, 182, 212, 0.05)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: hoursLabels,
            datasets: [{
                label: 'Activity Probability %',
                data: diurnalData,
                backgroundColor: gradient,
                borderColor: '#06b6d4',
                borderWidth: 1.5,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#888888', font: { family: 'Fira Code' } },
                    min: 0,
                    max: 100
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
