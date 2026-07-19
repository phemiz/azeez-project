<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$csrfToken = \App\Core\Session::generateCSRFToken();
$initials = strtoupper(substr($user['username'], 0, 2));
?>
<div class="space-y-8 animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-800" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase">Operator Profile Center</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Manage your personal coordinates, rotate credentials, and audit active sessions</span>
        </div>
        <a href="<?= APP_URL ?>/dashboard" class="btn-secondary text-xs py-1.5 px-3">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Return to Workspace</span>
        </a>
    </div>

    <!-- Feedback Message Widget -->
    <div id="profileAlert" class="hidden p-4 rounded-xl text-xs font-mono font-medium border flex items-start space-x-2">
        <i id="alertIcon" class="w-4 h-4 mt-0.5"></i>
        <span id="alertMessage" class="flex-1"></span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Avatar & 2FA Setup -->
        <div class="space-y-6">
            <!-- Avatar Card -->
            <div class="cyber-card p-6 flex flex-col items-center text-center space-y-4">
                <div class="relative group">
                    <div class="w-24 h-24 rounded-full border-2 border-dashed border-cyan-500/30 flex items-center justify-center overflow-hidden bg-slate-900">
                        <?php if (!empty($user['avatar'])): ?>
                            <img id="avatarImage" src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" class="w-full h-full object-cover" />
                        <?php else: ?>
                            <span id="avatarPlaceholder" class="text-2xl font-bold text-cyan-400 font-mono"><?= $initials ?></span>
                        <?php endif; ?>
                    </div>
                    <!-- Upload overlay -->
                    <label for="avatarUploadInput" class="absolute inset-0 rounded-full bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center cursor-pointer transition-opacity duration-200">
                        <i data-lucide="camera" class="w-6 h-6 text-white"></i>
                        <input type="file" id="avatarUploadInput" class="hidden" accept="image/*" onchange="uploadAvatarFile()" />
                    </label>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-white font-mono uppercase"><?= htmlspecialchars($user['username']) ?></h3>
                    <span class="text-3xs uppercase font-mono tracking-widest" style="color: var(--color-primary);"><?= $user['status'] ?> Node Operator</span>
                </div>
            </div>

            <!-- Two-Factor Authenticator Placeholder -->
            <div class="cyber-card p-6 space-y-4">
                <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b pb-3" style="border-color: var(--color-border);">
                    <i data-lucide="shield-check" class="w-4.5 h-4.5 text-cyan-400"></i>
                    <span>Multi-Factor Authentication</span>
                </h3>

                <div class="space-y-3 text-xs font-mono">
                    <div class="p-3.5 rounded-xl border border-cyan-500/20 bg-cyan-500/5 text-cyan-400 flex items-start gap-3">
                        <i data-lucide="info" class="w-5 h-5 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <strong class="block uppercase">TOTP MFA Enabled</strong>
                            <span class="text-[10px] text-gray-300">TOTP (Google Authenticator) active. Additional cellular SMS OTP required during transaction validations.</span>
                        </div>
                    </div>

                    <button class="btn-secondary w-full py-2 text-2xs justify-center cursor-pointer">
                        <span>Reset Auth Keys</span>
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Middle Column: Profile Info & Passcode Rotations -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Edit Coordinates Form -->
            <div class="cyber-card p-6 space-y-4">
                <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b pb-3" style="border-color: var(--color-border);">
                    <i data-lucide="user" class="w-4.5 h-4.5 text-cyan-400"></i>
                    <span>Profile coordinates Information</span>
                </h3>

                <form id="profileCoordsForm" class="space-y-4 text-xs">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>" />

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Username (Read-Only)</label>
                        <input type="text" readonly value="<?= htmlspecialchars($user['username']) ?>" class="cyber-input font-mono bg-slate-900/50 cursor-not-allowed border-slate-900" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Secure Email Coordinate</label>
                            <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>" class="cyber-input font-mono" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Encrypted Phone Coordinate</label>
                            <input type="text" name="phone" required value="<?= htmlspecialchars($user['phone']) ?>" class="cyber-input font-mono" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="btn-primary py-2 px-6 font-mono text-xs">
                            <span>Update Profile Info</span>
                            <i data-lucide="save" class="w-4 h-4"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Passcode rotation card -->
            <div class="cyber-card p-6 space-y-4">
                <h3 class="text-xs font-bold text-white font-mono uppercase tracking-wider flex items-center gap-1.5 border-b pb-3" style="border-color: var(--color-border);">
                    <i data-lucide="key-round" class="w-4.5 h-4.5 text-cyan-400"></i>
                    <span>Rotate credentials passcode</span>
                </h3>

                <form id="rotatePasscodeForm" class="space-y-4 text-xs">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>" />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Current Password</label>
                            <input type="password" name="old_password" required placeholder="Enter current passcode" class="cyber-input" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">New Password</label>
                            <input type="password" name="new_password" required placeholder="Min 8 characters" class="cyber-input" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="btn-primary py-2 px-6 font-mono text-xs">
                            <span>Rotate Passcode</span>
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Active Sessions & Security Audits Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Active Sessions -->
        <div class="cyber-card p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-4 font-mono flex items-center justify-between">
                <span>Active Browser session Footprints</span>
                <i data-lucide="shield-check" class="w-4 h-4 text-cyan-400"></i>
            </h3>

            <div class="space-y-3 font-mono text-2xs">
                <?php if (empty($sessions)): ?>
                    <div class="text-center py-4 text-gray-500">No active login sessions cataloged.</div>
                <?php else: ?>
                    <?php foreach ($sessions as $s): ?>
                        <div class="p-3 border rounded-xl bg-slate-900 border-slate-800 space-y-2">
                            <div class="flex justify-between items-center border-b pb-1.5 border-slate-850">
                                <span class="text-white font-bold block max-w-[200px] truncate" title="<?= htmlspecialchars($s['user_agent']) ?>"><?= htmlspecialchars($s['user_agent']) ?></span>
                                <span class="px-2 py-0.5 rounded text-[8px] font-bold bg-cyan-500/10 text-cyan-400">ACTIVE SESSION</span>
                            </div>
                            <div class="flex justify-between text-3xs text-gray-400">
                                <span>IP: <?= htmlspecialchars($s['ip_address']) ?></span>
                                <span>Last Action: <?= date('Y-m-d H:i', $s['last_activity']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activities list -->
        <div class="cyber-card p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-white mb-4 font-mono flex items-center justify-between">
                <span>Recent profile Security Logs</span>
                <i data-lucide="file-check-2" class="w-4 h-4 text-cyan-400"></i>
            </h3>

            <div class="space-y-3 font-mono text-2xs">
                <?php if (empty($recentLogs)): ?>
                    <div class="text-center py-4 text-gray-500">No transactions recorded inside this security cycle.</div>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="p-3 border border-slate-900 bg-slate-900/10 rounded-xl flex items-start justify-between">
                            <div>
                                <span class="text-white block font-bold"><?= htmlspecialchars($log['action']) ?></span>
                                <span class="text-[10px]" style="color: var(--color-foreground-muted);">Details: <?= htmlspecialchars($log['threat_details'] ?: 'No alert details recorded.') ?></span>
                            </div>
                            <span class="text-[10px] text-gray-500 font-mono text-right shrink-0"><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Update Profile Coordinates
document.getElementById('profileCoordsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const alertBox = document.getElementById('profileAlert');
    const alertMessage = document.getElementById('alertMessage');
    const alertIcon = document.getElementById('alertIcon');

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/profile/update', {
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

            setTimeout(() => window.location.reload(), 1200);
        } else {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-red-500/30 bg-red-500/10 text-red-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-x');
            alertMessage.textContent = data.message || 'Details update failed.';
            alertBox.classList.remove('hidden');
            lucide.createIcons();
        }
    } catch(e) {
        alert('Gateway communication failure.');
    }
});

// Rotate Passcode
document.getElementById('rotatePasscodeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const alertBox = document.getElementById('profileAlert');
    const alertMessage = document.getElementById('alertMessage');
    const alertIcon = document.getElementById('alertIcon');

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/profile/password', {
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

            setTimeout(() => window.location.reload(), 1200);
        } else {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-red-500/30 bg-red-500/10 text-red-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-x');
            alertMessage.textContent = data.message || 'Passcode rotation failed.';
            alertBox.classList.remove('hidden');
            lucide.createIcons();
        }
    } catch(e) {
        alert('Gateway communication failure.');
    }
});

// Upload Avatar File
async function uploadAvatarFile() {
    const input = document.getElementById('avatarUploadInput');
    const alertBox = document.getElementById('profileAlert');
    const alertMessage = document.getElementById('alertMessage');
    const alertIcon = document.getElementById('alertIcon');

    if (!input.files || input.files.length === 0) return;

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
            // Update UI avatar elements
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

            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-check');
            alertMessage.textContent = data.message;
            alertBox.classList.remove('hidden');
            lucide.createIcons();
        } else {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-red-500/30 bg-red-500/10 text-red-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-x');
            alertMessage.textContent = data.message || 'Avatar upload failed.';
            alertBox.classList.remove('hidden');
            lucide.createIcons();
        }
    } catch (e) {
        alert('Network execution failure.');
    }
}
</script>
