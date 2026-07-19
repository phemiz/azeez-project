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
    
    <!-- Left Panel: Secure OTP Entry Terminal -->
    <div class="w-full max-w-md p-8 rounded-2xl cyber-card border border-cyan-500/20 relative overflow-hidden">
        <div class="absolute -top-24 -left-24 w-48 h-48 rounded-full bg-cyan-500/5 blur-3xl pointer-events-none"></div>
        
        <div class="text-center mb-6 relative z-10">
            <div class="inline-flex bg-cyan-500/10 p-3 rounded-full border border-cyan-500/30 mb-3 animate-pulse">
                <i data-lucide="key-round" class="w-6 h-6 text-cyan-400"></i>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-white mb-2 font-mono uppercase">Two-Factor Auth</h1>
            <p class="text-xs" style="color: var(--color-foreground-muted);">An encrypted verification code was dispatched to your GSM node</p>
        </div>

        <form id="otpForm" class="space-y-6 relative z-10">
            <!-- CSRF Protection -->
            <input type="hidden" name="csrf_token" id="csrfToken" value="<?= $csrfToken ?>">

            <!-- OTP Digit Entries -->
            <div>
                <label class="block text-center text-2xs font-bold uppercase tracking-wider mb-4" style="color: var(--color-primary);">Enter 6-Digit Signal Key</label>
                
                <div class="flex justify-center gap-2.5" id="otpContainer">
                    <input type="text" maxlength="1" class="otp-digit w-11 h-12 bg-slate-900 border border-slate-800 rounded-xl text-center font-mono text-xl font-bold focus:outline-none focus:border-cyan-500 transition-all" style="background-color: var(--color-surface); border-color: var(--color-border); color: var(--color-primary);" />
                    <input type="text" maxlength="1" class="otp-digit w-11 h-12 bg-slate-900 border border-slate-800 rounded-xl text-center font-mono text-xl font-bold focus:outline-none focus:border-cyan-500 transition-all" style="background-color: var(--color-surface); border-color: var(--color-border); color: var(--color-primary);" />
                    <input type="text" maxlength="1" class="otp-digit w-11 h-12 bg-slate-900 border border-slate-800 rounded-xl text-center font-mono text-xl font-bold focus:outline-none focus:border-cyan-500 transition-all" style="background-color: var(--color-surface); border-color: var(--color-border); color: var(--color-primary);" />
                    <input type="text" maxlength="1" class="otp-digit w-11 h-12 bg-slate-900 border border-slate-800 rounded-xl text-center font-mono text-xl font-bold focus:outline-none focus:border-cyan-500 transition-all" style="background-color: var(--color-surface); border-color: var(--color-border); color: var(--color-primary);" />
                    <input type="text" maxlength="1" class="otp-digit w-11 h-12 bg-slate-900 border border-slate-800 rounded-xl text-center font-mono text-xl font-bold focus:outline-none focus:border-cyan-500 transition-all" style="background-color: var(--color-surface); border-color: var(--color-border); color: var(--color-primary);" />
                    <input type="text" maxlength="1" class="otp-digit w-11 h-12 bg-slate-900 border border-slate-800 rounded-xl text-center font-mono text-xl font-bold focus:outline-none focus:border-cyan-500 transition-all" style="background-color: var(--color-surface); border-color: var(--color-border); color: var(--color-primary);" />
                </div>
                <!-- Hidden input carrying actual data -->
                <input type="hidden" name="otp_code" id="otpCode" required />
            </div>

            <!-- Countdown Timer -->
            <div id="timerContainer" class="text-xs text-center font-mono" style="color: var(--color-foreground-muted);">
                Signal validation closes in: <span id="timerText" style="color: var(--color-primary); font-weight: bold;">05:00</span>
            </div>

            <!-- Alert Container -->
            <div id="alertBox" class="hidden p-4 rounded-xl text-xs font-mono font-medium border flex items-start space-x-2">
                <i id="alertIcon" class="w-4 h-4 mt-0.5"></i>
                <span id="alertMessage" class="flex-1"></span>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="submitBtn" class="btn-primary w-full justify-center">
                <span>Unlock Terminal</span>
                <i data-lucide="unlock" class="w-4 h-4"></i>
            </button>
        </form>

        <div class="text-center mt-6 relative z-10 font-mono">
            <span class="text-xs" style="color: var(--color-foreground-muted);">Didn't receive code? </span>
            <button id="resendOtp" class="text-xs hover:underline font-bold transition-colors cursor-pointer" style="color: var(--color-primary); background: none; border: none; padding: 0;">Re-transmit Signals</button>
        </div>
    </div>

    <!-- Right Panel: Simulated GSM Handset (Developer Helper) -->
    <div class="w-80 h-[500px] bg-slate-900 rounded-[36px] border-4 border-gray-800 relative shadow-2xl p-4 flex flex-col justify-between overflow-hidden" style="background-color: #0b0f19;">
        <!-- Ear speaker -->
        <div class="absolute top-2 left-1/2 transform -translate-x-1/2 w-28 h-4 bg-black rounded-full z-10 flex items-center justify-center space-x-2">
            <div class="w-12 h-1 bg-gray-800 rounded-full"></div>
            <div class="w-2.5 h-2.5 bg-gray-900 rounded-full"></div>
        </div>

        <!-- Phone screen -->
        <div class="flex-grow bg-slate-950 rounded-[24px] border border-gray-800 p-4 flex flex-col justify-between relative overflow-hidden" style="background-color: #030712; border-color: var(--color-border);">
            <!-- Glow background -->
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-cyan-500/10 rounded-full blur-2xl"></div>

            <!-- Phone Top Bar -->
            <div class="flex justify-between items-center text-[10px] font-mono mt-1" style="color: var(--color-primary);">
                <span id="phoneClock">14:45</span>
                <div class="flex items-center space-x-1">
                    <i data-lucide="signal" class="w-3 h-3 animate-pulse" style="color: var(--color-primary);"></i>
                    <span>LTE</span>
                    <i data-lucide="battery" class="w-3.5 h-3.5"></i>
                </div>
            </div>

            <!-- Simulated SMS Alert -->
            <div class="my-auto space-y-4 relative z-10">
                <div class="bg-gray-900/90 border border-cyan-500/20 p-4 rounded-2xl shadow-lg animate-bounce" style="background-color: var(--color-surface); border-color: var(--color-border);">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold tracking-wider flex items-center space-x-1" style="color: var(--color-primary);">
                            <i data-lucide="message-square-code" class="w-3.5 h-3.5 mr-1"></i>
                            GSM_NODE_SMS
                        </span>
                        <span class="text-[8px] font-mono" style="color: var(--color-foreground-muted);">Just Now</span>
                    </div>
                    <p class="text-xs font-sans leading-relaxed" style="color: var(--color-foreground);">
                        GSM-GUARD Warning: Your verification code is 
                        <span id="phoneOtpText" class="font-mono font-bold text-sm tracking-wider underline" style="color: var(--color-primary);">
                            <?= htmlspecialchars($simulated_otp ?? '******') ?>
                        </span>. 
                        Valid for 5 minutes.
                    </p>
                </div>

                <div class="text-center font-mono">
                    <span class="text-[9px] uppercase tracking-widest block" style="color: var(--color-primary); opacity: 0.6;">Simulated GSM Link</span>
                    <span class="text-[8px]" style="color: var(--color-foreground-muted);">Bypasses local hardware transceivers</span>
                </div>
            </div>

            <!-- Home Bar -->
            <div class="w-20 h-1 bg-gray-800 rounded-full mx-auto mt-2"></div>
        </div>
    </div>
</div>

<script>
// Digit Inputs focus triggers
const digits = document.querySelectorAll('.otp-digit');
const codeHidden = document.getElementById('otpCode');

digits.forEach((digit, index) => {
    digit.addEventListener('input', (e) => {
        digit.value = digit.value.replace(/[^0-9]/g, ''); // enforce integers only
        if (digit.value && index < digits.length - 1) {
            digits[index + 1].focus();
        }
        updateHiddenValue();
    });

    digit.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !digit.value && index > 0) {
            digits[index - 1].focus();
        }
    });
});

function updateHiddenValue() {
    let combined = '';
    digits.forEach(d => combined += d.value);
    codeHidden.value = combined;
}

// 5-Minute Countdown Timer
let timerSeconds = 300;
let countdownInterval;

function startTimer() {
    clearInterval(countdownInterval);
    timerSeconds = 300;
    
    const timerText = document.getElementById('timerText');
    const submitBtn = document.getElementById('submitBtn');
    
    // Enable fields
    digits.forEach(d => {
        d.disabled = false;
        d.value = '';
    });
    codeHidden.value = '';
    submitBtn.disabled = false;

    countdownInterval = setInterval(() => {
        timerSeconds--;
        const mins = Math.floor(timerSeconds / 60).toString().padStart(2, '0');
        const secs = (timerSeconds % 60).toString().padStart(2, '0');
        timerText.textContent = `${mins}:${secs}`;

        if (timerSeconds <= 60) {
            timerText.style.color = 'var(--color-accent)'; // Warn in last 60 seconds
        } else {
            timerText.style.color = 'var(--color-primary)';
        }

        if (timerSeconds <= 0) {
            clearInterval(countdownInterval);
            timerText.textContent = "OTP EXPIRED";
            timerText.style.color = 'var(--color-accent)';
            
            // Disable entries
            digits.forEach(d => d.disabled = true);
            submitBtn.disabled = true;
            ToastManager.show("Verification window expired. Re-transmit signals.", "error");
        }
    }, 1000);
}

// Verify OTP ajax submit
document.getElementById('otpForm').addEventListener('submit', async function(e) {
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
        <span>Verifying Token...</span>
    `;

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/verify-otp', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            clearInterval(countdownInterval);
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-check');
            alertMessage.textContent = data.message;
            alertBox.classList.remove('hidden');
            lucide.createIcons();
            
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1200);
        } else {
            alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-rose-500/30 bg-rose-500/10 text-rose-400 flex items-start space-x-2";
            alertIcon.setAttribute('data-lucide', 'shield-x');
            alertMessage.textContent = data.message || 'OTP validation failed.';
            alertBox.classList.remove('hidden');
            lucide.createIcons();
            
            // Reset digits
            digits.forEach(d => d.value = '');
            codeHidden.value = '';
            digits[0].focus();

            submitBtn.disabled = false;
            submitBtn.innerHTML = `<span>Unlock Terminal</span><i data-lucide="unlock" class="w-4 h-4"></i>`;
            lucide.createIcons();
        }
    } catch (err) {
        alertBox.className = "p-4 rounded-xl text-xs font-mono font-medium border border-rose-500/30 bg-rose-500/10 text-rose-400 flex items-start space-x-2";
        alertIcon.setAttribute('data-lucide', 'shield-alert');
        alertMessage.textContent = "Network error. Server gateway unreachable.";
        alertBox.classList.remove('hidden');
        lucide.createIcons();

        submitBtn.disabled = false;
        submitBtn.innerHTML = `<span>Unlock Terminal</span><i data-lucide="unlock" class="w-4 h-4"></i>`;
        lucide.createIcons();
    }
});

// Ajax Resend OTP trigger
document.getElementById('resendOtp').addEventListener('click', async function(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData();
        formData.append('csrf_token', document.getElementById('csrfToken').value);

        const response = await fetch('<?= APP_URL ?>/resend-otp', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            // Update simulated phone UI with new code
            document.getElementById('phoneOtpText').textContent = data.simulated_otp;
            
            // Restart countdown and enable inputs
            startTimer();
            digits[0].focus();
            
            ToastManager.show("New verification signal transmitted.");
        } else {
            ToastManager.show(data.message || "Failed to re-transmit token.", "error");
        }
    } catch (err) {
        ToastManager.show("Network error. Unable to re-transmit signals.", "error");
    }
});

// Update simulated handset clock time
function updateHandsetClock() {
    const now = new Date();
    const hrs = now.getHours().toString().padStart(2, '0');
    const mins = now.getMinutes().toString().padStart(2, '0');
    document.getElementById('phoneClock').textContent = `${hrs}:${mins}`;
}

// Initializations
document.addEventListener('DOMContentLoaded', () => {
    startTimer();
    updateHandsetClock();
    setInterval(updateHandsetClock, 60000);
});
</script>
