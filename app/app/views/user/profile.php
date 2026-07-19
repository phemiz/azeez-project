<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$csrfToken = \App\Core\Session::generateCSRFToken();
$initials = strtoupper(substr($user['username'] ?? 'OP', 0, 2));
$currentSessionId = session_id();

// Set defaults for missing DB columns if any
$mfaEnabled = (int)($user['mfa_enabled'] ?? 1);
$loginNotify = (int)($user['login_notify'] ?? 1);
$sessionTimeoutCustom = (int)($user['session_timeout_custom'] ?? 900);
?>
<div class="space-y-8 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-6 border-b border-cyan-500/10 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white font-mono uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="shield-user" class="w-7 h-7 text-cyan-400"></i>
                <span>Operator Profile Center</span>
            </h1>
            <span class="text-xs text-gray-400 font-mono">Manage your personal credentials, customize node security parameters, and monitor session footprints.</span>
        </div>
        <a href="<?= APP_URL ?>/dashboard" class="btn-secondary text-xs py-2 px-4 flex items-center gap-2 self-start sm:self-center">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Return to Workspace</span>
        </a>
    </div>

    <!-- Alert Banner -->
    <div id="profileAlert" class="hidden p-4 rounded-xl text-xs font-mono font-medium border flex items-start space-x-2 transition-all duration-300">
        <i id="alertIcon" class="w-4 h-4 mt-0.5 shrink-0"></i>
        <span id="alertMessage" class="flex-1"></span>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Column 1: Identity & Multi-Factor Auth -->
        <div class="space-y-6">
            <!-- Identity Avatar Card -->
            <div class="cyber-card flex flex-col items-center text-center space-y-4">
                <div class="relative group">
                    <div class="w-28 h-28 rounded-full border-2 border-dashed border-cyan-400 flex items-center justify-center overflow-hidden bg-gray-950 relative shadow-inner">
                        <?php if (!empty($user['avatar'])): ?>
                            <img id="avatarImage" src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" class="w-full h-full object-cover" />
                        <?php else: ?>
                            <span id="avatarPlaceholder" class="text-3xl font-extrabold text-cyan-400 font-mono tracking-widest"><?= $initials ?></span>
                        <?php endif; ?>
                    </div>
                    <!-- Upload overlay -->
                    <label for="avatarUploadInput" class="absolute inset-0 rounded-full bg-black/75 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center cursor-pointer transition-opacity duration-200 border border-cyan-500/50">
                        <i data-lucide="upload-cloud" class="w-6 h-6 text-cyan-400 animate-bounce"></i>
                        <span class="text-[9px] text-cyan-400 font-mono font-bold uppercase mt-1">Upload</span>
                        <input type="file" id="avatarUploadInput" class="hidden" accept="image/*" onchange="uploadAvatarFile()" />
                    </label>
                </div>

                <div class="space-y-1">
                    <h3 class="text-base font-bold text-white font-mono uppercase tracking-wide"><?= htmlspecialchars($user['username'] ?? 'operator') ?></h3>
                    <div class="flex items-center justify-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[9px] font-mono font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 uppercase"><?= htmlspecialchars($user['role'] ?? 'user') ?> Node</span>
                        <span class="px-2 py-0.5 rounded text-[9px] font-mono font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase"><?= htmlspecialchars($user['status'] ?? 'active') ?></span>
                    </div>
                    <span class="text-[10px] text-gray-500 font-mono block pt-1">Registered: <?= date('Y-m-d H:i', strtotime($user['created_at'] ?? 'now')) ?></span>
                </div>
            </div>

            <!-- Two-Factor Authentication Mockup Card -->
            <div class="cyber-card space-y-4">
                <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b border-cyan-500/10 pb-3">
                    <i data-lucide="shield-check" class="w-4.5 h-4.5 text-cyan-400"></i>
                    <span>Two-Factor Authentication</span>
                </h3>

                <div class="space-y-4 text-xs font-mono">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400">MFA Enforce Status:</span>
                        <span id="mfaBadge" class="px-2 py-0.5 rounded text-[9px] font-bold <?= $mfaEnabled ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-400 border border-rose-500/30' ?>">
                            <?= $mfaEnabled ? 'ACTIVE (TOTP + SMS)' : 'DISABLED' ?>
                        </span>
                    </div>

                    <!-- Simulated QR Setup -->
                    <div id="mfaSetupWidget" class="p-3 bg-gray-950/70 border border-cyan-500/10 rounded-xl flex flex-col items-center gap-3">
                        <!-- Custom CSS/SVG QR code placeholder for cyber-shield aesthetic -->
                        <div class="w-32 h-32 bg-white p-2 rounded-lg flex items-center justify-center relative shadow-lg">
                            <svg viewBox="0 0 100 100" class="w-full h-full text-gray-950">
                                <!-- Draw mock QR patterns -->
                                <rect x="0" y="0" width="30" height="30" fill="currentColor"/>
                                <rect x="5" y="5" width="20" height="20" fill="white"/>
                                <rect x="10" y="10" width="10" height="10" fill="currentColor"/>
                                
                                <rect x="70" y="0" width="30" height="30" fill="currentColor"/>
                                <rect x="75" y="5" width="20" height="20" fill="white"/>
                                <rect x="80" y="10" width="10" height="10" fill="currentColor"/>
                                
                                <rect x="0" y="70" width="30" height="30" fill="currentColor"/>
                                <rect x="5" y="75" width="20" height="20" fill="white"/>
                                <rect x="10" y="80" width="10" height="10" fill="currentColor"/>
                                
                                <rect x="40" y="10" width="10" height="20" fill="currentColor"/>
                                <rect x="40" y="40" width="20" height="10" fill="currentColor"/>
                                <rect x="10" y="45" width="20" height="15" fill="currentColor"/>
                                <rect x="80" y="40" width="10" height="25" fill="currentColor"/>
                                <rect x="45" y="65" width="15" height="10" fill="currentColor"/>
                                <rect x="65" y="75" width="15" height="20" fill="currentColor"/>
                                <rect x="40" y="80" width="15" height="15" fill="currentColor"/>
                                
                                <!-- Center decorative shield icon -->
                                <rect x="40" y="40" width="20" height="20" fill="white"/>
                                <rect x="42" y="42" width="16" height="16" fill="#00FF41" rx="2"/>
                            </svg>
                        </div>
                        <div class="text-center space-y-1">
                            <span class="text-[9px] text-gray-500 uppercase block">Secret Config Key</span>
                            <span id="mfaSecretKey" class="text-[11px] font-bold text-cyan-400 tracking-wider">GSM-SECURE-OP-<?= str_pad($user['id'], 3, '0', STR_PAD_LEFT) ?>-SEED</span>
                        </div>
                    </div>

                    <button type="button" onclick="regenerateMfaKeys()" class="btn-secondary w-full py-2 text-[10px] justify-center gap-2">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Reset Auth Keys</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Column 2: Personal Info, Passcode & Settings -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Personal Info Card -->
            <div class="cyber-card space-y-4">
                <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b border-cyan-500/10 pb-3">
                    <i data-lucide="user" class="w-4.5 h-4.5 text-cyan-400"></i>
                    <span>Profile Coordinates</span>
                </h3>

                <form id="profileCoordsForm" class="space-y-4 text-xs font-mono">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Username (Read-Only)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                    <i data-lucide="user-cog" class="w-4 h-4"></i>
                                </span>
                                <input type="text" readonly value="<?= htmlspecialchars($user['username'] ?? '') ?>" class="cyber-input pl-9 bg-gray-950/50 cursor-not-allowed border-gray-900 text-gray-400" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">User Role (Read-Only)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                                </span>
                                <input type="text" readonly value="<?= htmlspecialchars(strtoupper($user['role'] ?? 'user')) ?> NODE OPERATOR" class="cyber-input pl-9 bg-gray-950/50 cursor-not-allowed border-gray-900 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Secure Email Coordinate</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                    <i data-lucide="mail" class="w-4 h-4 text-cyan-500/70"></i>
                                </span>
                                <input type="email" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="cyber-input pl-9" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">GSM Phone Coordinate</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                    <i data-lucide="phone" class="w-4 h-4 text-cyan-500/70"></i>
                                </span>
                                <input type="text" name="phone" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="cyber-input pl-9" placeholder="+1234567890" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="btn-primary py-2 px-6 font-mono text-xs">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Update Profile Info</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Passcode Rotation Card -->
            <div class="cyber-card space-y-4">
                <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b border-cyan-500/10 pb-3">
                    <i data-lucide="key-round" class="w-4.5 h-4.5 text-cyan-400"></i>
                    <span>Credential Rotation</span>
                </h3>

                <form id="rotatePasscodeForm" class="space-y-4 text-xs font-mono">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Current Password</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                    <i data-lucide="lock" class="w-4 h-4"></i>
                                </span>
                                <input type="password" name="old_password" required placeholder="Verify current credentials" class="cyber-input pl-9" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">New Password</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                    <i data-lucide="key" class="w-4 h-4"></i>
                                </span>
                                <input type="password" name="new_password" required placeholder="Min 8 chars, mixed case + symbol" class="cyber-input pl-9" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="btn-primary py-2 px-6 font-mono text-xs">
                            <i data-lucide="shield-alert" class="w-4 h-4"></i>
                            <span>Rotate Passcode</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Preferences Card -->
            <div class="cyber-card space-y-4">
                <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b border-cyan-500/10 pb-3">
                    <i data-lucide="sliders" class="w-4.5 h-4.5 text-cyan-400"></i>
                    <span>Security Settings</span>
                </h3>

                <form id="securitySettingsForm" class="space-y-4 text-xs font-mono">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Toggles -->
                        <div class="space-y-4">
                            <div class="flex items-start justify-between p-3 rounded-lg bg-gray-950/30 border border-gray-900">
                                <div class="space-y-1">
                                    <strong class="block text-white text-[11px]">Enforce Two-Factor</strong>
                                    <span class="text-[9px] text-gray-400">Require TOTP and cellular SMS OTP validation codes.</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" name="mfa_enabled" value="1" class="sr-only peer" <?= $mfaEnabled ? 'checked' : '' ?> />
                                    <div class="w-9 h-5 bg-gray-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:height-4 after:w-4 after:h-4 after:transition-all peer-checked:bg-cyan-500"></div>
                                </label>
                            </div>

                            <div class="flex items-start justify-between p-3 rounded-lg bg-gray-950/30 border border-gray-900">
                                <div class="space-y-1">
                                    <strong class="block text-white text-[11px]">Login Alert Notifications</strong>
                                    <span class="text-[9px] text-gray-400">Transmit audit reports for every successful operator login.</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer mt-1">
                                    <input type="checkbox" name="login_notify" value="1" class="sr-only peer" <?= $loginNotify ? 'checked' : '' ?> />
                                    <div class="w-9 h-5 bg-gray-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:height-4 after:w-4 after:h-4 after:transition-all peer-checked:bg-cyan-500"></div>
                                </label>
                            </div>
                        </div>

                        <!-- Right Dropdowns -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Custom Session Timeout</label>
                                <select name="session_timeout" class="cyber-input font-mono">
                                    <option value="300" <?= $sessionTimeoutCustom === 300 ? 'selected' : '' ?>>5 Minutes (Strict)</option>
                                    <option value="900" <?= $sessionTimeoutCustom === 900 ? 'selected' : '' ?>>15 Minutes (Default)</option>
                                    <option value="1800" <?= $sessionTimeoutCustom === 1800 ? 'selected' : '' ?>>30 Minutes (Relaxed)</option>
                                    <option value="3600" <?= $sessionTimeoutCustom === 3600 ? 'selected' : '' ?>>1 Hour (Extended)</option>
                                </select>
                                <span class="text-[9px] text-gray-500 mt-1.5 block">Overrides default system session timeout constraints.</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-cyan-500/10">
                        <button type="submit" class="btn-primary py-2 px-6 font-mono text-xs">
                            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                            <span>Save Preferences</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- Active Sessions & Security Audits Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Active Browser Sessions Grid -->
        <div class="cyber-card flex flex-col justify-between">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-4 font-mono flex items-center justify-between border-b border-cyan-500/10 pb-3">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="monitor" class="text-cyan-400 w-4 h-4"></i>
                        <span>Active Browser Session Footprints</span>
                    </span>
                    <span class="text-[9px] px-2 py-0.5 rounded bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 font-bold"><?= count($sessions) ?> Active</span>
                </h3>

                <div class="space-y-4 font-mono text-xs">
                    <?php if (empty($sessions)): ?>
                        <div class="text-center py-8 text-gray-500">No active login sessions cataloged.</div>
                    <?php else: ?>
                        <?php foreach ($sessions as $s): ?>
                            <?php $isCurrent = ($s['id'] === $currentSessionId); ?>
                            <div class="p-4 border rounded-xl bg-gray-950/40 border-gray-900 hover:border-cyan-500/25 transition-all duration-200 space-y-3">
                                <div class="flex justify-between items-start">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <!-- Device/OS Icons -->
                                            <?php if ($s['device'] === 'Mobile'): ?>
                                                <i data-lucide="smartphone" class="w-4 h-4 text-cyan-400"></i>
                                            <?php elseif ($s['device'] === 'Tablet'): ?>
                                                <i data-lucide="tablet" class="w-4 h-4 text-cyan-400"></i>
                                            <?php else: ?>
                                                <i data-lucide="monitor" class="w-4 h-4 text-cyan-400"></i>
                                            <?php endif; ?>
                                            <strong class="text-white text-xs block"><?= htmlspecialchars($s['os']) ?> &bull; <?= htmlspecialchars($s['browser']) ?></strong>
                                        </div>
                                        <span class="text-[10px] text-gray-500 truncate block max-w-[280px]" title="<?= htmlspecialchars($s['user_agent']) ?>"><?= htmlspecialchars($s['user_agent']) ?></span>
                                    </div>
                                    
                                    <?php if ($isCurrent): ?>
                                        <span class="px-2 py-0.5 rounded text-[8px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                            <span>CURRENT DEVICE</span>
                                        </span>
                                    <?php else: ?>
                                        <button type="button" onclick="revokeSession('<?= $s['id'] ?>')" class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white rounded border border-rose-500/30 hover:border-rose-500 transition-all font-mono text-[9px] font-bold uppercase cursor-pointer">
                                            Revoke
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="flex justify-between text-[10px] text-gray-400 pt-1 border-t border-gray-900/50">
                                    <span>IP Coordinate: <?= htmlspecialchars($s['ip_address']) ?></span>
                                    <span>Last Activity: <?= date('Y-m-d H:i:s', $s['last_activity']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activities list / Audit Trails -->
        <div class="cyber-card flex flex-col justify-between">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-4 font-mono flex items-center justify-between border-b border-cyan-500/10 pb-3">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="file-check-2" class="text-cyan-400 w-4 h-4"></i>
                        <span>Recent Profile Security Logs</span>
                    </span>
                    <i data-lucide="activity" class="w-4 h-4 text-cyan-500/50 animate-pulse"></i>
                </h3>

                <div class="relative border-l-2 border-cyan-500/20 pl-4 ml-2 space-y-4 py-2">
                    <?php if (empty($recentLogs)): ?>
                        <div class="text-center py-8 text-gray-500 font-mono text-xs">No transactions recorded inside this security cycle.</div>
                    <?php else: ?>
                        <?php foreach ($recentLogs as $log): ?>
                            <div class="relative space-y-1">
                                <!-- Dot indicator on timeline -->
                                <span class="absolute -left-[23px] top-1.5 w-2.5 h-2.5 rounded-full bg-cyan-500 border border-gray-950 shadow-inner"></span>
                                
                                <div class="flex justify-between items-start">
                                    <span class="text-white text-xs font-bold font-mono uppercase tracking-wide"><?= htmlspecialchars(str_replace('_', ' ', $log['action'])) ?></span>
                                    <span class="text-[9px] text-gray-500 font-mono"><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></span>
                                </div>
                                <div class="text-[10px] text-gray-400 font-mono">
                                    <span>IP: <?= htmlspecialchars($log['ip_address']) ?></span>
                                    <span class="mx-1.5 text-gray-700">|</span>
                                    <span class="text-[9px] text-gray-400/80"><?= htmlspecialchars($log['threat_details'] ?? 'Transaction authenticated successfully.') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Update Profile Coordinates Form
document.getElementById('profileCoordsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    SpinnerManager.show();

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/profile/update', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            ToastManager.show(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            ToastManager.show(data.message || 'Details update failed.', 'error');
        }
    } catch(e) {
        ToastManager.show('Gateway communication failure.', 'error');
    } finally {
        SpinnerManager.hide();
    }
});

// Rotate Passcode Form
document.getElementById('rotatePasscodeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    SpinnerManager.show();

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/profile/password', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            ToastManager.show(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            ToastManager.show(data.message || 'Passcode rotation failed.', 'error');
        }
    } catch(e) {
        ToastManager.show('Gateway communication failure.', 'error');
    } finally {
        SpinnerManager.hide();
    }
});

// Update Security Preferences Settings Form
document.getElementById('securitySettingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    SpinnerManager.show();

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/profile/security', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            ToastManager.show(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            ToastManager.show(data.message || 'Settings update failed.', 'error');
        }
    } catch(e) {
        ToastManager.show('Gateway communication failure.', 'error');
    } finally {
        SpinnerManager.hide();
    }
});

// Revoke Session
async function revokeSession(sessionId) {
    if (!confirm('Are you sure you want to terminate this active browser session footprint? The remote device will be instantly signed out.')) {
        return;
    }
    SpinnerManager.show();

    try {
        const formData = new FormData();
        formData.append('session_id', sessionId);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/profile/sessions/revoke', {
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

// Upload Avatar File
async function uploadAvatarFile() {
    const input = document.getElementById('avatarUploadInput');
    if (!input.files || input.files.length === 0) return;
    SpinnerManager.show();

    try {
        const formData = new FormData();
        formData.append('avatar', input.files[0]);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        const response = await fetch('<?= APP_URL ?>/profile/avatar', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            const img = document.getElementById('avatarImage');
            if (img) {
                img.src = data.avatar_url;
            } else {
                const placeholder = document.getElementById('avatarPlaceholder');
                if (placeholder) {
                    const newImg = document.createElement('img');
                    newImg.id = 'avatarImage';
                    newImg.src = data.avatar_url;
                    newImg.className = "w-full h-full object-cover";
                    placeholder.parentNode.replaceChild(newImg, placeholder);
                }
            }

            ToastManager.show(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            ToastManager.show(data.message || 'Avatar upload failed.', 'error');
        }
    } catch (e) {
        ToastManager.show('Network execution failure.', 'error');
    } finally {
        SpinnerManager.hide();
    }
}

// Mock Keys Regeneration
function regenerateMfaKeys() {
    SpinnerManager.show();
    setTimeout(() => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        let key = '';
        for (let i = 0; i < 16; i++) {
            key += chars.charAt(Math.floor(Math.random() * chars.length));
            if (i === 3 || i === 7 || i === 11) key += '-';
        }
        document.getElementById('mfaSecretKey').textContent = 'GSM-MOCK-' + key;
        ToastManager.show('Dynamic Authenticator Key Seed Rotated.', 'success');
        SpinnerManager.hide();
    }, 600);
}
</script>
