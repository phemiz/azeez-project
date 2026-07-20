<?php
// Safety Check: block direct inclusions
if (!defined('ENTRY_SECURE')) {
    exit('Direct access not permitted.');
}
use App\Core\Session;

$user = Session::get('user');
$isAdmin = $user && ($user['role'] === 'admin' || $user['role'] === 'super');
$activeUri = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!-- Sidebar Navigation Partial -->
<aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col h-screen fixed left-0 top-0 z-40" style="background-color: var(--color-surface); border-color: var(--color-border);">
    <!-- Brand Title Header -->
    <div class="h-16 flex items-center px-6 border-b border-slate-800" style="border-color: var(--color-border);">
        <a href="<?= APP_URL ?>" class="flex items-center gap-3">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: var(--color-primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <span class="font-mono text-base font-bold uppercase tracking-wider" style="color: var(--color-foreground-title);">GSM GUARD</span>
        </a>
    </div>

    <!-- Scrollable Navigation Body Links -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <!-- Dashboard Workspace -->
        <a href="<?= APP_URL ?>/dashboard" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm transition-all duration-150 hover:bg-slate-800 hover:text-white"
           style="color: var(--color-foreground); transition: background-color 150ms ease;">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"/>
            </svg>
            <span>Workspace</span>
        </a>

        <!-- Global Search -->
        <a href="<?= APP_URL ?>/search" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm transition-all duration-150 hover:bg-slate-800 hover:text-white"
           style="color: var(--color-foreground); transition: background-color 150ms ease;">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <span>Global Search</span>
        </a>

        <!-- Notifications -->
        <a href="<?= APP_URL ?>/notifications" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm transition-all duration-150 hover:bg-slate-800 hover:text-white"
           style="color: var(--color-foreground); transition: background-color 150ms ease;">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9"/>
            </svg>
            <span>Notifications</span>
        </a>

        <!-- Admin Terminal controls (RBAC Gated) -->
        <?php if ($isAdmin): ?>
            <a href="<?= APP_URL ?>/admin" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm transition-all duration-150 hover:bg-slate-800 hover:text-white"
               style="color: var(--color-foreground); transition: background-color 150ms ease;">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                <span>Admin Panel</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Bottom Profile Meta Bar -->
    <div class="p-4 border-t border-slate-800 flex flex-col gap-3" style="border-color: var(--color-border); background-color: rgba(0,0,0,0.05);">
        <a href="<?= APP_URL ?>/profile" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
            <?php if (!empty($user['avatar'])): ?>
                <img src="<?= htmlspecialchars($user['avatar']) ?>" class="h-8 w-8 rounded-full object-cover border border-cyan-500/30" />
            <?php else: ?>
                <span class="h-8 w-8 rounded-full bg-slate-800 flex items-center justify-center font-bold text-xs uppercase" style="background-color: var(--color-border); color: var(--color-primary);">
                    <?= substr($user['username'] ?? 'O', 0, 2) ?>
                </span>
            <?php endif; ?>
            <div class="flex flex-col">
                <span class="text-xs font-semibold" style="color: var(--color-foreground-title);"><?= htmlspecialchars($user['username'] ?? 'Operator') ?></span>
                <span class="text-2xs font-mono uppercase" style="color: var(--color-primary);"><?= ($user['role'] ?? '') === 'super' ? 'SuperAdmin' : ($isAdmin ? 'SecAdmin' : 'SecUser') ?></span>
            </div>
        </a>
        
        <!-- Log Out CTA Button -->
        <a href="<?= APP_URL ?>/logout" class="flex items-center justify-center gap-2 text-xs font-semibold py-2 border rounded-lg hover:bg-red-500 hover:text-white hover:border-red-500 transition-all duration-150"
           style="color: var(--color-foreground); border-color: var(--color-border);">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
