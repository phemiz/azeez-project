<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Generate CSRF token
$csrfToken = \App\Core\Session::generateCSRFToken();
?>
<div class="flex items-center justify-center min-h-[75vh]">
    <div class="w-full max-w-lg p-8 rounded-2xl cyber-card border border-cyan-500/20 relative overflow-hidden">
        
        <!-- Glow accents -->
        <div class="absolute -top-24 -left-24 w-48 h-48 rounded-full bg-cyan-500/5 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-48 h-48 rounded-full bg-cyan-500/5 blur-3xl pointer-events-none"></div>

        <div class="text-center mb-6 relative z-10 font-mono">
            <h1 class="text-xl font-bold tracking-tight text-white mb-2 uppercase">Reset Node Passcode</h1>
            <p class="text-xs" style="color: var(--color-foreground-muted);">Establishing new cipher passcode for node operator: <span style="color: var(--color-primary); font-weight: bold;"><?= htmlspecialchars($username) ?></span></p>
        </div>

        <form id="resetForm" class="space-y-4 relative z-10">
            <!-- CSRF Protection -->
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <!-- Recovery Token -->
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <!-- Passcodes Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- New Password -->
                <div>
                    <label for="password" class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">New Cipher Passcode</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center" style="color: var(--color-foreground-muted);">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </span>
                        <input type="password" id="password" name="password" required 
                               class="cyber-input pl-10 pr-10" 
                               placeholder="••••••••">
                        <button type="button" onclick="togglePasswordVisibility('password', 'eyeIcon1')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" style="color: var(--color-foreground-muted);">
                            <i id="eyeIcon1" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Confirm Passcode</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center" style="color: var(--color-foreground-muted);">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               class="cyber-input pl-10 pr-10" 
                               placeholder="••••••••">
                        <button type="button" onclick="togglePasswordVisibility('confirm_password', 'eyeIcon2')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" style="color: var(--color-foreground-muted);">
                            <i id="eyeIcon2" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dynamic Password Entropy Strength Meter -->
            <div>
                <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden" style="background-color: var(--color-surface); border: 1px solid var(--color-border);">
                    <div id="strengthBar" class="h-full w-0 bg-red-500 transition-all duration-300"></div>
                </div>
                <div class="flex justify-between items-center text-[10px] mt-1 font-mono">
                    <span id="strengthText" style="color: var(--color-foreground-muted);">Entropy Check</span>
                    <span style="color: var(--color-foreground-muted);">[Min 8 chars, case variation, number, symbol]</span>
                </div>
            </div>

            <!-- Alert Container -->
            <div id="alertBox" class="hidden p-4 rounded-xl text-xs font-mono font-medium border flex items-start space-x-2">
                <i id="alertIcon" class="w-4 h-4 mt-0.5"></i>
                <span id="alertMessage" class="flex-1"></span>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="submitBtn" class="btn-primary w-full justify-center">
                <span>Deploy New Passcode</span>
                <i data-lucide="shield-check" class="w-4 h-4"></i>
            </button>
        </form>
    </div>
</div>

<script>
// Password Visibility Toggler
function togglePasswordVisibility(id, iconId) {
    const input = document.getElementById(id);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    lucide.createIcons();
}

// Client-side Password Strength Meter
document.getElementById('password').addEventListener('input', function() {
    const val = this.value;
    const bar = document.getElementById('strengthBar');
    const txt = document.getElementById('strengthText');
    
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    if (val.length === 0) {
        bar.className = 'h-full w-0 transition-all duration-300';
        txt.textContent = 'Entropy Check';
        txt.style.color = 'var(--color-foreground-muted)';
    } else if (score <= 2) {
        bar.className = 'h-full w-1/3 bg-red-600 transition-all duration-300';
        txt.textContent = 'CRITICAL: WEAK PASSWORD ENTROPY';
        txt.style.color = '#ef4444';
    } else if (score <= 4) {
        bar.className = 'h-full w-2/3 bg-amber-500 transition-all duration-300';
        txt.textContent = 'WARNING: MEDIUM PASSWORD ENTROPY';
        txt.style.color = '#f59e0b';
    } else {
        bar.className = 'h-full w-full bg-emerald-500 transition-all duration-300';
        txt.textContent = 'SECURE: STRONG PASSWORD ENTROPY';
        txt.style.color = '#10b981';
    }
});

// Ajax submit form
document.getElementById('resetForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const alertBox = document.getElementById('alertBox');
    const alertMessage = document.getElementById('alertMessage');
    const alertIcon = document.getElementById('alertIcon');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-black" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Resetting Passcode...</span>
    `;

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/reset-password', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-check');
            alertMessage.textContent = data.message;
            alertBox.classList.remove('hidden');
            lucide.createIcons();
            
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 2000);
        } else {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-rose-500/30 bg-rose-500/10 text-rose-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-x');
            alertMessage.textContent = data.message || 'Passcode reset failed.';
            alertBox.classList.remove('hidden');
            lucide.createIcons();
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<span>Deploy New Passcode</span><i data-lucide="shield-check" class="w-4 h-4"></i>`;
            lucide.createIcons();
        }
    } catch (err) {
        alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-rose-500/30 bg-rose-500/10 text-rose-400 flex items-start space-x-2";
        alertIcon.setAttribute('data-lucide', 'shield-alert');
        alertMessage.textContent = "Network error. Gateway unreachable.";
        alertBox.classList.remove('hidden');
        lucide.createIcons();

        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span>Deploy New Passcode</span><i data-lucide="shield-check" class="w-4 h-4"></i>`;
        lucide.createIcons();
    }
});
</script>
