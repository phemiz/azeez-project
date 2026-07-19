<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Generate CSRF token
$csrfToken = \App\Core\Session::generateCSRFToken();
?>
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Breadcrumb / Header -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-800" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase">Cryptographic Enveloping Terminal</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">High-security AES-256-CBC & PBKDF2 data protection shield</span>
        </div>
        <a href="<?= APP_URL ?>/dashboard" class="btn-secondary text-xs py-1.5 px-3">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Return to Workspace</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Form Parameters -->
        <div class="col-span-2 space-y-6">
            <div class="cyber-card">
                <form id="dedicatedEncryptForm" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Recipient GSM MSISDN</label>
                            <input type="text" name="recipient" required placeholder="+2348030000000" class="cyber-input font-mono" />
                        </div>
                        <div>
                            <label class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Key Derivation Phrase</label>
                            <input type="password" name="passphrase" required placeholder="Shared secret passphrase" class="cyber-input" />
                        </div>
                    </div>

                    <!-- GSM Satellites Simulation Metadata (WAF targets) -->
                    <div class="p-4 rounded-xl border space-y-3" style="background-color: rgba(0,0,0,0.15); border-color: var(--color-border);">
                        <span class="text-2xs text-gray-400 block font-mono font-bold tracking-widest uppercase">GSM Link Simulation Context</span>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-2xs font-semibold block mb-1" style="color: var(--color-primary);">Service Center (SMSC)</label>
                                <input type="text" name="smsc" value="234803000000" placeholder="e.g. +234803..." class="cyber-input font-mono text-xs" />
                                <span class="text-[8px]" style="color: var(--color-foreground-muted);">Provide '0000000000' to simulate an IMSI Catcher</span>
                            </div>
                            <div>
                                <label class="text-2xs font-semibold block mb-1" style="color: var(--color-primary);">Protocol Identifier (PID)</label>
                                <select name="protocol_id" class="cyber-input text-xs">
                                    <option value="0">0x00 - Standard SMS</option>
                                    <option value="64">0x40 - Type 0 Silent SMS (Triangulation)</option>
                                </select>
                                <span class="text-[8px]" style="color: var(--color-foreground-muted);">Silent SMS triggers tracking alerts</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Raw Message Payload</label>
                        <textarea name="message" rows="4" required placeholder="Type confidential data payload..." class="cyber-input"></textarea>
                    </div>

                    <button type="submit" id="btn-submit" class="btn-primary w-full justify-center">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                        <span>Transmit Encrypted Envelope</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Real-time Threat Intel & Results -->
        <div class="space-y-6">
            <!-- AI Recommendation Status Panel -->
            <div id="aiCard" class="cyber-card bg-cyan-500/5 space-y-3 relative overflow-hidden">
                <div class="flex items-center gap-2">
                    <div class="bg-cyan-500/10 p-2 rounded-lg border border-cyan-500/30">
                        <i data-lucide="brain-circuit" class="w-5 h-5 text-cyan-400"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider font-mono">Heuristic Threat status</h3>
                        <span class="text-[10px] block" style="color: var(--color-foreground-muted);">Evaluating transmission link integrity</span>
                    </div>
                </div>
                <div id="aiBox" class="space-y-2">
                    <p class="text-xs font-mono leading-relaxed" style="color: var(--color-primary);">
                        Link safe. No active carrier anomalies detected. Encrypted parameters will be stored securely in 3NF database.
                    </p>
                </div>
            </div>

            <!-- Envelope Output Display -->
            <div id="encryptResult" class="cyber-card hidden space-y-3" style="border-color: var(--color-primary); background-color: var(--color-surface);">
                <span class="text-xs font-mono font-bold flex items-center gap-1.5 animate-pulse" style="color: var(--color-primary);">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    <span>Envelope Secured</span>
                </span>
                
                <div class="space-y-2">
                    <label class="text-[10px] block font-mono" style="color: var(--color-foreground-muted);">Ciphertext Output:</label>
                    <textarea id="output-ciphertext" readonly class="w-full text-2xs bg-black/40 border border-slate-800 rounded-lg p-2 font-mono h-20 focus:outline-none" style="color: var(--color-primary);"></textarea>
                </div>

                <div class="space-y-2 font-mono text-[9px]">
                    <div>
                        <span style="color: var(--color-foreground-muted);" class="block">Random IV:</span>
                        <input id="output-iv" type="text" readonly class="w-full bg-black/40 border border-slate-800 rounded p-1 focus:outline-none" style="color: var(--color-primary);" />
                    </div>
                    <div>
                        <span style="color: var(--color-foreground-muted);" class="block">Salt:</span>
                        <input id="output-salt" type="text" readonly class="w-full bg-black/40 border border-slate-800 rounded p-1 focus:outline-none" style="color: var(--color-primary);" />
                    </div>
                    <div>
                        <span style="color: var(--color-foreground-muted);" class="block">HMAC MAC:</span>
                        <input id="output-signature" type="text" readonly class="w-full bg-black/40 border border-slate-800 rounded p-1 focus:outline-none" style="color: var(--color-primary);" />
                    </div>
                </div>

                <button onclick="copyEnvelope()" class="btn-secondary w-full justify-center text-xs">
                    <i data-lucide="copy" class="w-4 h-4"></i>
                    <span>Copy Parameter Block</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('dedicatedEncryptForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit');
    const resPanel = document.getElementById('encryptResult');
    
    btn.disabled = true;
    btn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-black" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Securing Envelope...</span>
    `;

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/encrypt', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            resPanel.classList.remove('hidden');
            document.getElementById('output-ciphertext').value = data.ciphertext;
            document.getElementById('output-iv').value = data.iv;
            document.getElementById('output-salt').value = data.salt;
            document.getElementById('output-signature').value = data.signature;

            updateAiProfile(data.ai_profile);
            ToastManager.show("GSM envelope secured and stored.");
        } else {
            ToastManager.show(data.message || "Enveloping failed.", "error");
            if (data.ai_profile) {
                updateAiProfile(data.ai_profile);
            }
        }
    } catch (err) {
        ToastManager.show("Network failure. Enveloping failed.", "error");
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="shield-check" class="w-4 h-4"></i><span>Transmit Encrypted Envelope</span>`;
        lucide.createIcons();
    }
});

function copyEnvelope() {
    const text = JSON.stringify({
        ciphertext: document.getElementById('output-ciphertext').value,
        iv: document.getElementById('output-iv').value,
        salt: document.getElementById('output-salt').value,
        signature: document.getElementById('output-signature').value
    });
    navigator.clipboard.writeText(text);
    ToastManager.show("Envelope credentials copied.");
}

function updateAiProfile(ai) {
    const box = document.getElementById('aiBox');
    const card = document.getElementById('aiCard');
    
    if (ai.risk_score >= 70) {
        card.className = "cyber-card bg-rose-500/5 space-y-3 relative overflow-hidden border-red-500 animate-pulse";
    } else if (ai.risk_score >= 30) {
        card.className = "cyber-card bg-amber-500/5 space-y-3 relative overflow-hidden border-amber-500";
    } else {
        card.className = "cyber-card bg-cyan-500/5 space-y-3 relative overflow-hidden";
    }

    box.innerHTML = `
        <div class="flex items-center justify-between text-xs mb-1 font-mono">
            <span style="color: var(--color-foreground-muted);">Heuristics Threat Rating:</span>
            <span class="font-bold ${ai.risk_score >= 70 ? 'text-rose-400' : (ai.risk_score >= 30 ? 'text-amber-400' : 'text-emerald-400')}">${ai.risk_score}%</span>
        </div>
        <div class="text-xs font-mono font-semibold ${ai.risk_score >= 30 ? 'text-rose-400' : 'text-cyan-400'}">
            Classification: ${ai.threat_classification}
        </div>
        <p class="text-[11px] leading-relaxed font-sans mt-2 border-t pt-2" style="border-color: var(--color-border); color: var(--color-foreground-muted);">
            <strong>Anomaly details:</strong> ${ai.threat_details}
        </p>
        <p class="text-[11px] font-mono leading-relaxed mt-1" style="color: var(--color-primary);">
            <strong>Mitigation Plan:</strong> ${ai.recommendations || 'System healthy. Continue.'}
        </p>
    `;
}
</script>
