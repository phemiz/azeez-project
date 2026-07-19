<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Generate CSRF token
$csrfToken = \App\Core\Session::generateCSRFToken();
?>
<div class="space-y-6 max-w-6xl mx-auto">
    <!-- Breadcrumb Header -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-800" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase">Cryptographic Decapsulation Terminal</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Decapsulate secure GSM envelopes using derived keys and integrity signatures</span>
        </div>
        <a href="<?= APP_URL ?>/dashboard" class="btn-secondary text-xs py-1.5 px-3">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Return to Workspace</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Form inputs and plain text outcome -->
        <div class="lg:col-span-2 space-y-6">
            <div class="cyber-card">
                <form id="dedicatedDecryptForm" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div>
                        <label class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Base64 Ciphertext Block</label>
                        <textarea name="ciphertext" id="input-ciphertext" rows="3" required placeholder="Paste base64 ciphertext payload block..." class="cyber-input font-mono text-xs"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="text-2xs font-mono font-semibold block mb-1" style="color: var(--color-primary);">Initialization Vector (IV)</label>
                            <input type="text" name="iv" id="input-iv" required placeholder="Base64 IV" class="cyber-input font-mono text-xs" />
                        </div>
                        <div>
                            <label class="text-2xs font-mono font-semibold block mb-1" style="color: var(--color-primary);">Key-Derivation Salt</label>
                            <input type="text" name="salt" id="input-salt" required placeholder="Base64 Salt" class="cyber-input font-mono text-xs" />
                        </div>
                        <div>
                            <label class="text-2xs font-mono font-semibold block mb-1" style="color: var(--color-primary);">HMAC Signature</label>
                            <input type="text" name="signature" id="input-signature" required placeholder="HMAC HMAC-SHA256" class="cyber-input font-mono text-xs" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Decryption Passphrase</label>
                        <input type="password" name="passphrase" required placeholder="Shared secret passphrase" class="cyber-input" />
                    </div>

                    <button type="submit" id="btn-submit" class="btn-primary w-full justify-center">
                        <i data-lucide="unlock" class="w-4 h-4"></i>
                        <span>Decrypt Envelope Payload</span>
                    </button>
                </form>
            </div>

            <!-- Plaintext Outcomes -->
            <div id="decryptResult" class="cyber-card hidden space-y-3" style="border-color: var(--color-primary); background-color: var(--color-surface);">
                <span class="text-xs font-mono font-bold flex items-center gap-1.5 animate-pulse" style="color: var(--color-primary);">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span>Payload Decapsulated Successfully</span>
                </span>
                <div class="bg-black/40 p-4 rounded-xl border border-slate-800 text-sm text-white font-mono break-all whitespace-pre-wrap leading-relaxed" id="output-plaintext"></div>
            </div>
        </div>

        <!-- Right: Message Envelopes History (IV Retrieval) -->
        <div class="space-y-6">
            <div class="cyber-card space-y-4">
                <div class="flex items-center justify-between border-b pb-3" style="border-color: var(--color-border);">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white font-mono">Sent Message History</h3>
                    <i data-lucide="folder-git" class="w-4 h-4" style="color: var(--color-primary);"></i>
                </div>

                <span class="text-[10px]" style="color: var(--color-foreground-muted);">Click an envelope below to load its IV, Salt, Signature, and Ciphertext automatically:</span>

                <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-6 text-xs" style="color: var(--color-foreground-muted);">No envelopes found in operator ledger.</div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div onclick="populateEnvelope('<?= htmlspecialchars(json_encode([
                                'ciphertext' => $msg['encrypted_payload'],
                                'iv'         => $msg['iv'],
                                'salt'       => $msg['salt'],
                                'signature'  => $msg['signature']
                            ])) ?>')" 
                                 class="p-3 rounded-lg border hover:bg-slate-800/10 cursor-pointer transition-all duration-150 space-y-1 font-mono text-[10px]" 
                                 style="border-color: var(--color-border); background-color: rgba(0,0,0,0.05);">
                                <div class="flex justify-between items-center text-white">
                                    <span class="font-bold">TO: <?= htmlspecialchars($msg['recipient']) ?></span>
                                    <span style="color: var(--color-foreground-muted);"><?= date('H:i m-d', strtotime($msg['created_at'])) ?></span>
                                </div>
                                <div class="truncate" style="color: var(--color-primary);"><?= substr($msg['encrypted_payload'], 0, 32) ?>...</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Populates decryption inputs from history selection
function populateEnvelope(jsonStr) {
    try {
        const payload = JSON.parse(jsonStr);
        document.getElementById('input-ciphertext').value = payload.ciphertext;
        document.getElementById('input-iv').value = payload.iv;
        document.getElementById('input-salt').value = payload.salt;
        document.getElementById('input-signature').value = payload.signature;
        
        ToastManager.show("Envelope parameters loaded successfully.");
    } catch(e) {
        ToastManager.show("Failed to load parameters.", "error");
    }
}

document.getElementById('dedicatedDecryptForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit');
    const resPanel = document.getElementById('decryptResult');
    const outField = document.getElementById('output-plaintext');

    btn.disabled = true;
    btn.innerHTML = `
        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-black" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Decapsulating...</span>
    `;

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/decrypt', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            resPanel.classList.remove('hidden');
            outField.textContent = data.plaintext;
            ToastManager.show("Decryption operation complete.");
            
            // Scroll down to display results
            resPanel.scrollIntoView({ behavior: 'smooth' });
        } else {
            ToastManager.show(data.message || "Decryption failed.", "error");
            resPanel.classList.add('hidden');
        }
    } catch (err) {
        ToastManager.show("Decryption network failure.", "error");
        resPanel.classList.add('hidden');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="unlock" class="w-4 h-4"></i><span>Decrypt Envelope Payload</span>`;
        lucide.createIcons();
    }
});
</script>
