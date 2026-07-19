<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Generate CSRF token
$csrfToken = \App\Core\Session::generateCSRFToken();
?>
<div class="flex flex-col md:flex-row items-center justify-center gap-12 min-h-[75vh] py-8">
    
    <!-- Left: Secure Administrative Login Terminal -->
    <div class="w-full max-w-md p-8 rounded-2xl cyber-card border border-red-500/30 relative overflow-hidden" style="background-color: var(--color-surface);">
        <!-- Warning Glow background -->
        <div class="absolute -top-24 -left-24 w-48 h-48 rounded-full bg-red-500/5 blur-3xl pointer-events-none"></div>
        
        <div class="text-center mb-8 relative z-10">
            <div class="inline-flex bg-red-500/10 p-3 rounded-full border border-red-500/30 mb-3 animate-pulse">
                <i data-lucide="shield-alert" class="w-6 h-6 text-red-500"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white mb-2 font-mono uppercase">Admin Terminal</h1>
            <p class="text-xs" style="color: var(--color-foreground-muted);">Access restricted to verified enterprise security administrators</p>
        </div>

        <form id="adminLoginForm" class="space-y-6 relative z-10">
            <!-- CSRF Protection -->
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <!-- Username Field -->
            <div>
                <label for="username" class="block text-2xs font-bold uppercase tracking-wider mb-1.5 text-red-500 font-mono">Admin Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center" style="color: var(--color-foreground-muted);">
                        <i data-lucide="user-cog" class="w-4 h-4"></i>
                    </span>
                    <input type="text" id="username" name="username" required 
                           class="cyber-input pl-10 focus:border-red-500" 
                           placeholder="admin_root">
                </div>
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-2xs font-bold uppercase tracking-wider mb-1.5 text-red-500 font-mono">Access Passcode</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center" style="color: var(--color-foreground-muted);">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                    </span>
                    <input type="password" id="password" name="password" required 
                           class="cyber-input pl-10 pr-10 focus:border-red-500" 
                           placeholder="••••••••">
                    <button type="button" onclick="togglePasswordVisibility('password', 'eyeIcon')" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" style="color: var(--color-foreground-muted);">
                        <i id="eyeIcon" data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Remember Node & Forgot Link -->
            <div class="flex items-center justify-between text-2xs font-mono">
                <label class="flex items-center space-x-2 text-gray-400">
                    <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-800 text-red-600 focus:ring-0 focus:ring-offset-0">
                    <span>Remember Node</span>
                </label>
                <a href="<?= APP_URL ?>/forgot-password" class="hover:underline text-red-400">Forgot Code?</a>
            </div>

            <!-- Alert Container -->
            <div id="alertBox" class="hidden p-4 rounded-xl text-xs font-mono font-medium border flex items-start space-x-2">
                <i id="alertIcon" class="w-4 h-4 mt-0.5"></i>
                <span id="alertMessage" class="flex-1"></span>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="submitBtn" 
                    class="w-full py-3 px-4 bg-gradient-to-r from-red-600 to-rose-700 text-white font-mono font-bold rounded-xl text-sm transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer flex items-center justify-center space-x-2 shadow-lg shadow-red-900/30">
                <span>Unlock Terminal</span>
                <i data-lucide="unlock" class="w-4 h-4"></i>
            </button>
        </form>

        <div class="text-center mt-6 relative z-10 font-mono">
            <a href="<?= APP_URL ?>/login" class="text-xs hover:underline text-gray-400">Return to User Portal</a>
        </div>
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

// Ajax submit admin form
document.getElementById('adminLoginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const alertBox = document.getElementById('alertBox');
    const alertMessage = document.getElementById('alertMessage');
    const alertIcon = document.getElementById('alertIcon');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Authorizing Admin...</span>
    `;

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/admin/login', {
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
            }, 1000);
        } else {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-red-500/30 bg-red-500/10 text-red-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-x');
            alertMessage.textContent = data.message || 'Authorization failed.';
            alertBox.classList.remove('hidden');
            lucide.createIcons();
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<span>Unlock Terminal</span><i data-lucide="unlock" class="w-4 h-4"></i>`;
            lucide.createIcons();
        }
    } catch (err) {
        alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-red-500/30 bg-red-500/10 text-red-400 flex items-start space-x-2";
        alertIcon.setAttribute('data-lucide', 'shield-alert');
        alertMessage.textContent = "Network error. Gateway unreachable.";
        alertBox.classList.remove('hidden');
        lucide.createIcons();

        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span>Unlock Terminal</span><i data-lucide="unlock" class="w-4 h-4"></i>`;
        lucide.createIcons();
    }
});
</script>
