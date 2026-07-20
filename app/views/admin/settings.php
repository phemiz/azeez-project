<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$csrfToken = \App\Core\Session::generateCSRFToken();
?>
<div class="space-y-6 max-w-4xl mx-auto animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-800" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase">App Settings Console</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Modify global system thresholds, security baselines, and communication channels</span>
        </div>
        <a href="<?= APP_URL ?>/admin" class="btn-secondary text-xs py-1.5 px-3">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Return to Terminal</span>
        </a>
    </div>

    <!-- Alert Box -->
    <div id="settingsAlert" class="hidden p-4 rounded-xl text-xs font-mono font-medium border flex items-start space-x-2">
        <i id="alertIcon" class="w-4 h-4 mt-0.5"></i>
        <span id="alertMessage" class="flex-1"></span>
    </div>

    <form id="settingsForm" class="space-y-8">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>" />

        <!-- 1. General Settings -->
        <div class="cyber-card p-6 space-y-4">
            <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b pb-2" style="border-color: var(--color-border);">
                <i data-lucide="settings" class="w-4 h-4 text-cyan-400"></i>
                <span>General Configurations</span>
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Application Name</label>
                    <input type="text" name="app_name" value="<?= htmlspecialchars($settings['app_name']) ?>" required class="cyber-input py-1.5" />
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Time Zone</label>
                    <select name="time_zone" class="cyber-input py-1.5 font-mono">
                        <option value="UTC" <?= $settings['time_zone'] === 'UTC' ? 'selected' : '' ?>>UTC / Greenwich</option>
                        <option value="GMT+1" <?= $settings['time_zone'] === 'GMT+1' ? 'selected' : '' ?>>WAT (GMT+1) / Lagos</option>
                        <option value="EST" <?= $settings['time_zone'] === 'EST' ? 'selected' : '' ?>>EST / New York</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Primary Theme Mode</label>
                    <select name="theme" class="cyber-input py-1.5 font-mono">
                        <option value="dark" <?= $settings['theme'] === 'dark' ? 'selected' : '' ?>>Cyber Dark (Default)</option>
                        <option value="light" <?= $settings['theme'] === 'light' ? 'selected' : '' ?>>Light Dashboard Mode</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">System Logo Identifier</label>
                    <input type="text" name="app_logo" value="<?= htmlspecialchars($settings['app_logo']) ?>" class="cyber-input py-1.5 font-mono" />
                </div>
            </div>
        </div>

        <!-- 2. Security & Session Settings -->
        <div class="cyber-card p-6 space-y-4">
            <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b pb-2" style="border-color: var(--color-border);">
                <i data-lucide="shield-check" class="w-4 h-4 text-cyan-400"></i>
                <span>Security & Sessions Gating</span>
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-xs">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">MFA Verification</label>
                    <select name="mfa_requirement" class="cyber-input py-1.5 font-mono">
                        <option value="forced" <?= $settings['mfa_requirement'] === 'forced' ? 'selected' : '' ?>>Forced on credentials change</option>
                        <option value="optional" <?= $settings['mfa_requirement'] === 'optional' ? 'selected' : '' ?>>Optional / Remember node</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Session Expiration (Seconds)</label>
                    <input type="number" name="session_timeout" value="<?= htmlspecialchars($settings['session_timeout']) ?>" required class="cyber-input py-1.5 font-mono" />
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">WAF Security Level</label>
                    <select name="security_level" class="cyber-input py-1.5 font-mono">
                        <option value="high" <?= $settings['security_level'] === 'high' ? 'selected' : '' ?>>High / Heuristics enabled</option>
                        <option value="medium" <?= $settings['security_level'] === 'medium' ? 'selected' : '' ?>>Medium / Simple thresholds</option>
                        <option value="low" <?= $settings['security_level'] === 'low' ? 'selected' : '' ?>>Low / Permissive audits</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 3. AI & Encryption Config -->
        <div class="cyber-card p-6 space-y-4">
            <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b pb-2" style="border-color: var(--color-border);">
                <i data-lucide="brain-circuit" class="w-4 h-4 text-cyan-400"></i>
                <span>AI Threats & Cryptography Settings</span>
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-xs">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">AI Threat Classification Level</label>
                    <select name="ai_detection_level" class="cyber-input py-1.5 font-mono">
                        <option value="high" <?= $settings['ai_detection_level'] === 'high' ? 'selected' : '' ?>>Strict anomalies tagging</option>
                        <option value="medium" <?= $settings['ai_detection_level'] === 'medium' ? 'selected' : '' ?>>Average classification</option>
                        <option value="low" <?= $settings['ai_detection_level'] === 'low' ? 'selected' : '' ?>>Disable AI analysis</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Encryption Algorithm Mode</label>
                    <select name="encryption_algorithm" class="cyber-input py-1.5 font-mono">
                        <option value="AES-256-CBC" <?= $settings['encryption_algorithm'] === 'AES-256-CBC' ? 'selected' : '' ?>>AES-256-CBC (OpenSSL)</option>
                        <option value="AES-128-CBC" <?= $settings['encryption_algorithm'] === 'AES-128-CBC' ? 'selected' : '' ?>>AES-128-CBC (Standard)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Backup Retention Period (Days)</label>
                    <input type="number" name="backup_retention_days" value="<?= htmlspecialchars($settings['backup_retention_days']) ?>" required class="cyber-input py-1.5 font-mono" />
                </div>
            </div>
        </div>

        <!-- 4. SMTP Email Channels Settings -->
        <div class="cyber-card p-6 space-y-4">
            <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b pb-2" style="border-color: var(--color-border);">
                <i data-lucide="mail" class="w-4 h-4 text-cyan-400"></i>
                <span>SMTP Channels Communications Settings</span>
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 text-xs">
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">SMTP Host Server</label>
                    <input type="text" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host']) ?>" required class="cyber-input py-1.5 font-mono" />
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">SMTP Port</label>
                    <input type="number" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port']) ?>" required class="cyber-input py-1.5 font-mono" />
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">SMTP Username</label>
                    <input type="text" name="smtp_user" value="<?= htmlspecialchars($settings['smtp_user']) ?>" required class="cyber-input py-1.5 font-mono" />
                </div>
                <div class="sm:col-span-4">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">SMTP Connection Passcode</label>
                    <input type="password" name="smtp_pass" value="<?= htmlspecialchars($settings['smtp_pass']) ?>" required class="cyber-input py-1.5 font-mono" />
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end pt-4">
            <button type="submit" class="btn-primary text-xs py-2 px-8 cursor-pointer">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>Apply Config Keys</span>
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const alertBox = document.getElementById('settingsAlert');
    const alertMessage = document.getElementById('alertMessage');
    const alertIcon = document.getElementById('alertIcon');

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/admin/settings/update', {
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
            
            // Scroll to alert
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-red-500/30 bg-red-500/10 text-red-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-x');
            alertMessage.textContent = data.message || 'Settings update failed.';
            alertBox.classList.remove('hidden');
            lucide.createIcons();

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    } catch(err) {
        alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-red-500/30 bg-red-500/10 text-red-400 flex items-start space-x-2";
        alertIcon.setAttribute('data-lucide', 'shield-alert');
        alertMessage.textContent = "Network error. Gateway unreachable.";
        alertBox.classList.remove('hidden');
        lucide.createIcons();

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});
</script>
