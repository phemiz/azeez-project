<?php
// Safety Check: block direct inclusions
if (!defined('ENTRY_SECURE')) {
    exit('Direct access not permitted.');
}
?>
<!-- Top Navigation Header Partial -->
<header class="h-16 flex items-center justify-between px-8 bg-slate-900 border-b border-slate-800 sticky top-0 z-30" style="background-color: var(--color-surface); border-color: var(--color-border);">
    
    <!-- System Telemetry Alert Status -->
    <div class="flex items-center gap-3">
        <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background-color: var(--color-primary);"></span>
            <span class="relative inline-flex rounded-full h-2 w-2" style="background-color: var(--color-primary);"></span>
        </span>
        <span class="text-xs font-mono font-bold uppercase tracking-wider hidden sm:inline-block" style="color: var(--color-foreground-muted);">System Threat State: <span style="color: var(--color-primary);">SECURE</span></span>
    </div>

    <!-- Right-aligned Action Panel (Theme Switcher and User Nodes metadata) -->
    <div class="flex items-center gap-6">
        
        <!-- Interactive Theme Switcher Toggle (Sun/Moon icons) -->
        <button onclick="ThemeManager.toggle()" class="p-2 rounded-lg border hover:bg-slate-800 transition-all duration-150 cursor-pointer" 
                style="border-color: var(--color-border); color: var(--color-foreground);" title="Toggle System Theme">
            <svg class="h-5 w-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
            </svg>
            <svg class="h-5 w-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>

        <!-- Connectivity State Badge -->
        <div class="flex items-center gap-2 px-3 py-1 border rounded-full text-xs font-mono" style="border-color: var(--color-border); background-color: rgba(0,0,0,0.02);">
            <span class="h-1.5 w-1.5 rounded-full" style="background-color: var(--color-primary);"></span>
            <span style="color: var(--color-foreground-muted);">SIGNAL: 98%</span>
        </div>
    </div>
</header>
