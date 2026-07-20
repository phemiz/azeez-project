<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$csrfToken = \App\Core\Session::generateCSRFToken();
?>
<div class="space-y-6 max-w-3xl mx-auto animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-800" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase">Disaster Recovery Restore Wizard</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Systematic restoration rollback checkpoint wizard for database tables and activity log streams</span>
        </div>
        <a href="<?= APP_URL ?>/admin/backups" class="btn-secondary text-xs py-1.5 px-3">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Return to Backups</span>
        </a>
    </div>

    <!-- Wizard Steps Indicator -->
    <div class="grid grid-cols-3 gap-4 text-center font-mono text-[10px] uppercase font-bold tracking-wider">
        <div id="step1Indicator" class="p-3 border border-cyan-500 bg-cyan-500/10 text-cyan-400 rounded-xl">Step 1: Select Checkpoint</div>
        <div id="step2Indicator" class="p-3 border border-slate-800 text-gray-500 rounded-xl">Step 2: Verify Integrity</div>
        <div id="step3Indicator" class="p-3 border border-slate-800 text-gray-500 rounded-xl">Step 3: Rollback Execution</div>
    </div>

    <!-- Alert Container -->
    <div id="wizardAlert" class="hidden p-4 rounded-xl text-xs font-mono font-medium border flex items-start space-x-2">
        <i id="alertIcon" class="w-4.5 h-4.5 mt-0.5"></i>
        <span id="alertMessage" class="flex-1"></span>
    </div>

    <!-- Step Content Area -->
    <div class="cyber-card p-8">
        
        <!-- Step 1: Select Checkpoint -->
        <div id="step1Content" class="space-y-6">
            <div class="space-y-2">
                <h3 class="text-sm font-bold text-white uppercase font-mono">Select Target Restoration Checkpoint</h3>
                <p class="text-xs" style="color: var(--color-foreground-muted);">Choose a database schema snapshot or activity log archive from the backups history ledger.</p>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5 font-mono">Available Snapshots</label>
                <select id="targetFile" class="cyber-input py-2 font-mono">
                    <option value="">-- Choose Checkpoint Snapshot --</option>
                    <?php foreach ($backups as $b): ?>
                        <option value="<?= htmlspecialchars($b['filename']) ?>"><?= htmlspecialchars($b['filename']) ?> (Size: <?= $b['filesize'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex justify-end pt-4">
                <button type="button" onclick="goToStep2()" class="btn-primary py-2 px-6 font-mono text-xs">
                    <span>Analyze Snapshot Integrity</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Step 2: Verify Integrity -->
        <div id="step2Content" class="hidden space-y-6">
            <div class="space-y-2">
                <h3 class="text-sm font-bold text-white uppercase font-mono">Integrity Auditing & Checks</h3>
                <p class="text-xs" style="color: var(--color-foreground-muted);">Verifying file signatures and parsing syntax declarations to ensure no corrupted files are written.</p>
            </div>

            <!-- Integrity audit cards -->
            <div class="space-y-4 font-mono text-xs">
                <div class="p-4 border rounded-xl bg-slate-900 border-slate-800 space-y-3">
                    <div class="flex justify-between border-b pb-2" style="border-color: var(--color-border);">
                        <span style="color: var(--color-foreground-muted);">SNAPSHOT TARGET:</span>
                        <span id="check-filename" class="text-white font-bold">N/A</span>
                    </div>
                    <div class="flex justify-between border-b pb-2" style="border-color: var(--color-border);">
                        <span style="color: var(--color-foreground-muted);">SHA-256 CHECK:</span>
                        <span id="check-hash" class="text-cyan-400">Calculating...</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-foreground-muted);">INTEGRITY DEC:</span>
                        <span id="check-status" class="text-amber-500 font-bold">Verifying...</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-between pt-4 border-t" style="border-color: var(--color-border);">
                <button type="button" onclick="goToStep1()" class="btn-secondary py-2 px-4 font-mono text-xs">Back</button>
                <button type="button" id="nextToStep3" disabled onclick="goToStep3()" class="btn-primary py-2 px-6 font-mono text-xs">
                    <span>Confirm Rollback</span>
                    <i data-lucide="shield-alert" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Step 3: Rollback execution -->
        <div id="step3Content" class="hidden space-y-6">
            <div class="space-y-2">
                <h3 class="text-sm font-bold text-white uppercase font-mono flex items-center gap-2">
                    <i data-lucide="shield-alert" class="text-rose-500 animate-pulse"></i>
                    <span>Execute Systematic Restoration</span>
                </h3>
                <p class="text-xs" style="color: var(--color-foreground-muted);">Database tables will be cleared and overridden. App settings and session hashes will revert to selected timestamp state.</p>
            </div>

            <!-- Progress Bar Card -->
            <div id="progressArea" class="hidden space-y-3">
                <div class="flex justify-between text-2xs font-mono">
                    <span id="progressLabel" class="text-cyan-400 animate-pulse">Decrypting archive payload...</span>
                    <span id="progressPercent" class="text-white font-bold">0%</span>
                </div>
                <div class="w-full h-2.5 bg-slate-900 border border-slate-800 rounded-full overflow-hidden">
                    <div id="progressBar" class="h-full bg-gradient-to-r from-cyan-500 to-emerald-500 transition-all duration-200" style="width: 0%;"></div>
                </div>
            </div>

            <div class="flex justify-between pt-4 border-t" style="border-color: var(--color-border);">
                <button type="button" id="backToStep2" onclick="goToStep2()" class="btn-secondary py-2 px-4 font-mono text-xs">Back</button>
                <button type="button" id="startRestoreBtn" onclick="runRestoration()" class="py-2.5 px-8 bg-gradient-to-r from-red-600 to-rose-700 hover:scale-[1.02] text-white font-mono font-bold rounded-xl text-xs flex items-center gap-2 cursor-pointer shadow-lg shadow-red-950/20">
                    <span>Deploy Rollback Point</span>
                    <i data-lucide="unlock" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

    </div>
</div>

<script>
let selectedFile = '';

function goToStep1() {
    document.getElementById('step2Content').classList.add('hidden');
    document.getElementById('step3Content').classList.add('hidden');
    document.getElementById('step1Content').classList.remove('hidden');

    document.getElementById('step2Indicator').className = 'p-3 border border-slate-800 text-gray-500 rounded-xl';
    document.getElementById('step3Indicator').className = 'p-3 border border-slate-800 text-gray-500 rounded-xl';
    document.getElementById('step1Indicator').className = 'p-3 border border-cyan-500 bg-cyan-500/10 text-cyan-400 rounded-xl';
}

async function goToStep2() {
    selectedFile = document.getElementById('targetFile').value;
    if (!selectedFile) {
        alert('Please choose a snapshot checkpoint first.');
        return;
    }

    document.getElementById('step1Content').classList.add('hidden');
    document.getElementById('step3Content').classList.add('hidden');
    document.getElementById('step2Content').classList.remove('hidden');

    document.getElementById('step1Indicator').className = 'p-3 border border-slate-800 text-gray-500 rounded-xl';
    document.getElementById('step3Indicator').className = 'p-3 border border-slate-800 text-gray-500 rounded-xl';
    document.getElementById('step2Indicator').className = 'p-3 border border-cyan-500 bg-cyan-500/10 text-cyan-400 rounded-xl';

    // Run verification check
    document.getElementById('check-filename').textContent = selectedFile;
    document.getElementById('check-hash').textContent = 'Calculating...';
    document.getElementById('check-status').className = 'text-amber-500 font-bold';
    document.getElementById('check-status').textContent = 'Verifying...';
    document.getElementById('nextToStep3').disabled = true;

    try {
        const formData = new FormData();
        formData.append('filename', selectedFile);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/admin/backups/verify', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            document.getElementById('check-hash').textContent = data.hash.substring(0, 32) + '...';
            if (data.check === 'verified') {
                document.getElementById('check-status').className = 'text-emerald-400 font-bold';
                document.getElementById('check-status').textContent = 'INTEGRITY VERIFIED (PASS)';
                document.getElementById('nextToStep3').disabled = false;
            } else {
                document.getElementById('check-status').className = 'text-rose-500 font-bold';
                document.getElementById('check-status').textContent = 'CORRUPTED OR INVALID FILE';
            }
        } else {
            document.getElementById('check-status').textContent = 'VERIFICATION ERROR';
        }
    } catch (e) {
        document.getElementById('check-status').textContent = 'NETWORK TIMEOUT';
    }
}

function goToStep3() {
    document.getElementById('step1Content').classList.add('hidden');
    document.getElementById('step2Content').classList.add('hidden');
    document.getElementById('step3Content').classList.remove('hidden');

    document.getElementById('step1Indicator').className = 'p-3 border border-slate-800 text-gray-500 rounded-xl';
    document.getElementById('step2Indicator').className = 'p-3 border border-slate-800 text-gray-500 rounded-xl';
    document.getElementById('step3Indicator').className = 'p-3 border border-cyan-500 bg-cyan-500/10 text-cyan-400 rounded-xl';
}

function runRestoration() {
    const alertBox = document.getElementById('wizardAlert');
    const alertMessage = document.getElementById('alertMessage');
    const alertIcon = document.getElementById('alertIcon');
    const progressArea = document.getElementById('progressArea');
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const progressLabel = document.getElementById('progressLabel');
    const startBtn = document.getElementById('startRestoreBtn');
    const backBtn = document.getElementById('backToStep2');

    startBtn.disabled = true;
    backBtn.disabled = true;
    progressArea.classList.remove('hidden');

    // Simulate progress increments for UI excellence
    let p = 0;
    const stages = [
        'Initializing rollback point...',
        'Decrypting backup payloads...',
        'Validating schemas integrity...',
        'Running table overrides...',
        'Syncing logs timelines...',
        'Finalizing recovery state...'
    ];

    const interval = setInterval(() => {
        p += 5;
        if (p > 100) p = 100;
        progressBar.style.width = p + '%';
        progressPercent.textContent = p + '%';
        
        const stageIdx = Math.min(stages.length - 1, Math.floor(p / 20));
        progressLabel.textContent = stages[stageIdx];

        if (p === 100) {
            clearInterval(interval);
            executeDbRestoreCall();
        }
    }, 120);

    async function executeDbRestoreCall() {
        try {
            const formData = new FormData();
            formData.append('filename', selectedFile);
            formData.append('csrf_token', '<?= $csrfToken ?>');

            const response = await fetch('<?= APP_URL ?>/admin/backups/restore', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (response.ok && data.status === 'success') {
                alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 flex items-start space-x-2";
                alertIcon.setAttribute('data-lucide', 'shield-check');
                alertMessage.textContent = 'Rollback success! System state restored successfully. Reloading workspace...';
                alertBox.classList.remove('hidden');
                lucide.createIcons();

                setTimeout(() => window.location.href = '<?= APP_URL ?>/admin', 1500);
            } else {
                alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-red-500/30 bg-red-500/10 text-red-400 flex items-start space-x-2";
                alertIcon.setAttribute('data-lucide', 'shield-x');
                alertMessage.textContent = data.message || 'Restoration task execution failed.';
                alertBox.classList.remove('hidden');
                lucide.createIcons();

                startBtn.disabled = false;
                backBtn.disabled = false;
            }
        } catch (e) {
            alert('Gateway network timeout during execution.');
            startBtn.disabled = false;
            backBtn.disabled = false;
        }
    }
}
</script>
