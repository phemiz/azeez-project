<?php
/**
 * GSM Guard Enterprise UI Components Blueprint
 * Copy-pasteable HTML component snippets configured with Tailwind and custom style tokens
 */
?>

<!-- 1. Cyber-Glow KPI Cards -->
<div class="cyber-card flex items-center justify-between">
    <div>
        <span class="text-xs font-mono uppercase tracking-wider text-slate-400 block" style="color: var(--color-foreground-muted);">Total Secure Nodes</span>
        <span class="text-2xl font-bold font-mono tracking-tight block mt-1" style="color: var(--color-foreground-title);">256 / 256</span>
    </div>
    <div class="p-3 rounded-lg" style="background-color: rgba(0, 255, 65, 0.05); border: 1px solid rgba(0, 255, 65, 0.2);">
        <i data-lucide="cpu" class="w-6 h-6" style="color: var(--color-primary);"></i>
    </div>
</div>

<!-- 2. Primary CTA Button (Press animation) -->
<button class="btn-primary">
    <i data-lucide="shield-check" class="w-4 h-4"></i>
    <span>Verify Integrity</span>
</button>

<!-- 3. Form Input Field -->
<div class="flex flex-col gap-2">
    <label class="text-xs font-mono uppercase tracking-wider font-bold" style="color: var(--color-foreground-muted);">Node Hostname</label>
    <input type="text" placeholder="node-01.carrier.net" class="cyber-input" />
</div>

<!-- 4. Data Dense Table -->
<div class="overflow-x-auto border rounded-xl" style="border-color: var(--color-border); background-color: var(--color-surface);">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b text-xs font-mono uppercase tracking-wider" style="border-color: var(--color-border); color: var(--color-foreground-muted); background-color: rgba(0,0,0,0.15);">
                <th class="px-6 py-4">Node Operator</th>
                <th class="px-6 py-4">IP Address</th>
                <th class="px-6 py-4">Security Level</th>
            </tr>
        </thead>
        <tbody class="divide-y text-sm font-mono" style="divide-color: var(--color-border); color: var(--color-foreground);">
            <tr class="hover:bg-slate-800/10" style="transition: background-color 150ms ease;">
                <td class="px-6 py-4 font-sans font-medium" style="color: var(--color-foreground-title);">Azeez Admin</td>
                <td class="px-6 py-4">192.168.1.100</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-0.5 rounded text-xs font-bold" style="background-color: rgba(0, 255, 65, 0.1); color: var(--color-primary); border: 1px solid rgba(0, 255, 65, 0.2);">ROOT</span>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- 5. Search Bar Box -->
<div class="relative">
    <input type="text" placeholder="Search node logs..." class="cyber-input pl-10" />
    <div class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-foreground-muted);">
        <i data-lucide="search" class="w-4 h-4"></i>
    </div>
</div>

<!-- 6. Alarm Alert Notification Panel -->
<div class="cyber-card animate-alert-pulse flex items-start gap-4" style="background-color: rgba(255, 51, 51, 0.04); border-color: var(--color-accent);">
    <div class="p-2 rounded-lg" style="background-color: rgba(255, 51, 51, 0.1); border: 1px solid rgba(255, 51, 51, 0.3);">
        <i data-lucide="alert-octagon" class="w-5 h-5" style="color: var(--color-accent);"></i>
    </div>
    <div>
        <h4 class="text-sm font-bold font-mono uppercase" style="color: var(--color-accent);">Base Station Jamming Attack Detected</h4>
        <p class="text-xs mt-1" style="color: var(--color-foreground-muted);">IMSI Catcher anomaly detected on cellular link interface #02. Frequency hopping triggered.</p>
    </div>
</div>

<!-- 7. Loading Spinner Overlay -->
<div class="flex items-center gap-3">
    <div class="h-4 w-4 border-2 border-t-transparent rounded-full animate-spin" style="border-color: var(--color-border); border-top-color: var(--color-primary);"></div>
    <span class="text-xs font-mono" style="color: var(--color-foreground-muted);">Encrypting GSM payload...</span>
</div>

<!-- 8. Interactive Modal Container (Default: hidden) -->
<div id="demo-modal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="cyber-card max-w-md w-full flex flex-col gap-6" style="background-color: var(--color-surface);">
        <div class="flex items-center justify-between border-b pb-4" style="border-color: var(--color-border);">
            <h3 class="text-base font-bold font-mono uppercase" style="color: var(--color-foreground-title);">Confirm Suspension</h3>
            <button onclick="ModalManager.close('demo-modal')" class="p-1 rounded hover:bg-slate-800 cursor-pointer" style="color: var(--color-foreground-muted);">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <p class="text-sm" style="color: var(--color-foreground);">This operator status will be marked suspended immediately, revoking all cryptographic keys access. Continue?</p>
        <div class="flex items-center justify-end gap-3">
            <button onclick="ModalManager.close('demo-modal')" class="btn-secondary">Cancel</button>
            <button class="btn-primary" style="background-color: var(--color-accent); border-color: var(--color-accent); color: white;">Confirm</button>
        </div>
    </div>
</div>
