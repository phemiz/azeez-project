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
    <div class="w-full max-w-md p-8 rounded-2xl cyber-card border border-cyan-500/20 relative overflow-hidden">
        
        <!-- Glow accents -->
        <div class="absolute -top-24 -left-24 w-48 h-48 rounded-full bg-cyan-500/5 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-48 h-48 rounded-full bg-cyan-500/5 blur-3xl pointer-events-none"></div>

        <div class="text-center mb-8 relative z-10">
            <div class="inline-flex bg-cyan-500/10 p-3 rounded-full border border-cyan-500/30 mb-3">
                <i data-lucide="key" class="w-6 h-6 text-cyan-400"></i>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-white mb-2 font-mono uppercase">Recover Terminal Access</h1>
            <p class="text-xs" style="color: var(--color-foreground-muted);">Enter registered email to receive passcode recovery link</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="mb-4 p-4 rounded-xl text-xs font-mono border border-rose-500/30 bg-rose-500/10 text-rose-400 flex items-start space-x-2">
                <i data-lucide="shield-x" class="w-4 h-4 mt-0.5"></i>
                <span class="flex-1"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form id="forgotForm" class="space-y-6 relative z-10">
            <!-- CSRF Protection -->
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <!-- Email Input -->
            <div>
                <label for="email" class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Operator Secure Email</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center" style="color: var(--color-foreground-muted);">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                    </span>
                    <input type="email" id="email" name="email" required 
                           class="cyber-input pl-10" 
                           placeholder="operator@gsmsecurity.net">
                </div>
            </div>

            <!-- Alert Container -->
            <div id="alertBox" class="hidden p-4 rounded-xl text-xs font-mono font-medium border flex items-start space-x-2">
                <i id="alertIcon" class="w-4 h-4 mt-0.5"></i>
                <span id="alertMessage" class="flex-1"></span>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="submitBtn" class="btn-primary w-full justify-center">
                <span>Request Recovery Token</span>
                <i data-lucide="send" class="w-4 h-4"></i>
            </button>
        </form>

        <!-- Developer Simulated SMS Handset Recovery Link Display (Helpful testing details) -->
        <?php if (\App\Core\Session::has('simulated_reset_link')): ?>
            <div class="mt-6 p-4 rounded-xl border border-cyan-500/20 bg-cyan-500/5 font-mono text-2xs space-y-2 relative z-10">
                <span class="text-cyan-400 font-bold block">Developer Recovery Link:</span>
                <a href="<?= \App\Core\Session::get('simulated_reset_link') ?>" class="text-emerald-400 underline break-all hover:text-emerald-300 block">
                    <?= htmlspecialchars(\App\Core\Session::get('simulated_reset_link')) ?>
                </a>
                <span class="text-gray-500 block text-[9px]">Click to reset this user's password directly in testing.</span>
            </div>
        <?php endif; ?>

        <div class="text-center mt-6 relative z-10 font-mono">
            <a href="<?= APP_URL ?>/login" class="text-xs hover:underline font-bold transition-colors" style="color: var(--color-primary);">Return to Portal Login</a>
        </div>
    </div>
</div>

<script>
document.getElementById('forgotForm').addEventListener('submit', async function(e) {
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
        <span>Transmitting Link...</span>
    `;

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/forgot-password', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            // Success
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-check');
            alertMessage.textContent = data.message;
            alertBox.classList.remove('hidden');
            lucide.createIcons();
            
            // Reload page to display simulated reset link if user exists
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-rose-500/30 bg-rose-500/10 text-rose-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-x');
            alertMessage.textContent = data.message || 'Transmission failed.';
            alertBox.classList.remove('hidden');
            lucide.createIcons();
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<span>Request Recovery Token</span><i data-lucide="send" class="w-4 h-4"></i>`;
            lucide.createIcons();
        }
    } catch (err) {
        alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-rose-500/30 bg-rose-500/10 text-rose-400 flex items-start space-x-2";
        alertIcon.setAttribute('data-lucide', 'shield-alert');
        alertMessage.textContent = "Network error. Gateway unreachable.";
        alertBox.classList.remove('hidden');
        lucide.createIcons();

        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span>Request Recovery Token</span><i data-lucide="send" class="w-4 h-4"></i>`;
        lucide.createIcons();
    }
});
</script>
