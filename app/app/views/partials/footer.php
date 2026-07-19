<?php
// Safety Check: block direct inclusions
if (!defined('ENTRY_SECURE')) {
    exit('Direct access not permitted.');
}
?>
<!-- System Footer Partial -->
<footer class="py-6 px-8 border-t border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4 mt-auto text-xs" 
        style="border-color: var(--color-border); background-color: var(--color-surface);">
    
    <div style="color: var(--color-foreground-muted);">
        &copy; <?= date('Y') ?> GSM GUARD. All rights reserved. Relational cryptographic telemetry active.
    </div>

    <!-- Active Protocol Badges -->
    <div class="flex items-center gap-3 font-mono text-2xs uppercase" style="color: var(--color-foreground-muted);">
        <span class="px-2 py-0.5 border rounded" style="border-color: var(--color-border);">AES-256-CBC</span>
        <span class="px-2 py-0.5 border rounded" style="border-color: var(--color-border);">PBKDF2-HMAC</span>
        <span class="px-2 py-0.5 border rounded" style="border-color: var(--color-border);">MFA ACTIVE</span>
        <span class="px-2 py-0.5 border rounded" style="border-color: var(--color-border);">HSTS-CSP</span>
    </div>
</footer>
