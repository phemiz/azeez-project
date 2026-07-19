<?php
// Generate CSRF token
$csrfToken = \App\Core\Session::generateCSRFToken();
?>

<div class="flex items-center justify-center min-h-[70vh]">
    <div class="w-full max-w-md p-8 rounded-2xl cyber-panel border border-cyan-500/20 relative overflow-hidden">
        
        <!-- Glow accents -->
        <div class="absolute -top-24 -left-24 w-48 h-48 rounded-full bg-cyan-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-48 h-48 rounded-full bg-cyan-500/10 blur-3xl pointer-events-none"></div>

        <div class="text-center mb-8 relative z-10">
            <h1 class="text-2xl font-bold tracking-tight text-white mb-2">Secure Login</h1>
            <p class="text-sm text-gray-400">Please enter your password to access messages</p>
        </div>

        <form id="loginForm" class="space-y-6 relative z-10">
            <!-- CSRF -->
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <!-- Username -->
            <div>
                <label for="username" class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center" style="color: var(--color-foreground-muted);">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </span>
                    <input type="text" id="username" name="username" required 
                           class="cyber-input pl-10" 
                           placeholder="Enter username">
                </div>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Password</label>
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

            <!-- Remember Me node checkbox -->
            <div class="flex items-center justify-between mt-2">
                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="remember" name="remember" 
                           class="rounded border-slate-800 text-cyan-500 focus:ring-0 cursor-pointer" 
                           style="background-color: var(--color-surface); border-color: var(--color-border);">
                    <label for="remember" class="text-xs select-none cursor-pointer" style="color: var(--color-foreground-muted);">Remember Me</label>
                </div>
            </div>

            <!-- Alert Container -->
            <div id="alertBox" class="hidden p-4 rounded-xl text-xs font-medium border flex items-start space-x-2">
                <div id="alertIconContainer" class="mt-0.5">
                    <i data-lucide="shield-alert" class="w-4 h-4"></i>
                </div>
                <span id="alertMessage" class="flex-1"></span>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="submitBtn" 
                    class="w-full py-3 px-4 bg-gradient-to-r from-cyan-500 to-emerald-500 text-black font-semibold rounded-xl text-sm transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer flex items-center justify-center space-x-2">
                <span>Log In</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </form>

        <div class="text-center mt-6 relative z-10">
            <span class="text-xs text-gray-500">New user? </span>
            <a href="<?= APP_URL ?>/register" class="text-xs text-cyan-400 hover:text-cyan-300 font-semibold transition-colors">Create an Account</a>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const alertBox = document.getElementById('alertBox');
    const alertMessage = document.getElementById('alertMessage');
    const alertIconContainer = document.getElementById('alertIconContainer');
    
    // Toggle Loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-black" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Checking credentials...</span>
    `;

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/login', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            // Success
            alertBox.className = "p-4 rounded-xl text-xs font-medium border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 flex items-start space-x-2";
            alertIconContainer.innerHTML = '<i data-lucide="shield-check" class="w-4 h-4"></i>';
            alertMessage.textContent = data.message;
            alertBox.classList.remove('hidden');
            lucide.createIcons();
            
            // Redirect
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1000);
        } else {
            // Error
            alertBox.className = "p-4 rounded-xl text-xs font-medium border border-rose-500/30 bg-rose-500/10 text-rose-400 flex items-start space-x-2";
            alertIconContainer.innerHTML = '<i data-lucide="shield-x" class="w-4 h-4"></i>';
            alertMessage.textContent = data.message || 'Authentication failed.';
            alertBox.classList.remove('hidden');
            lucide.createIcons();
            
            // Reset button
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<span>Log In</span><i data-lucide="arrow-right" class="w-4 h-4"></i>`;
            lucide.createIcons();
        }
    } catch (err) {
        alertBox.className = "p-4 rounded-xl text-xs font-medium border border-rose-500/30 bg-rose-500/10 text-rose-400 flex items-start space-x-2";
        alertIconContainer.innerHTML = '<i data-lucide="shield-alert" class="w-4 h-4"></i>';
        alertMessage.textContent = "Network error. Remote server unreachable.";
        alertBox.classList.remove('hidden');
        lucide.createIcons();

        // Reset button
        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span>Log In</span><i data-lucide="arrow-right" class="w-4 h-4"></i>`;
        lucide.createIcons();
    }
});

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
</script>
