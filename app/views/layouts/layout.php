<?php
// Safety check: Prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'GSM Data Protection Platform') ?></title>
    
    <!-- Google Fonts (Outfit & JetBrains Mono for secure keys) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Components Custom styling and script -->
    <link rel="stylesheet" href="<?= APP_URL ?>/css/components.css">
    <script src="<?= APP_URL ?>/js/components.js"></script>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        cyber: {
                            black: '#030712',
                            dark: '#0b0f19',
                            card: '#111827',
                            glow: '#06b6d4',
                            emerald: '#10b981',
                            alert: '#f43f5e',
                            warning: '#f59e0b'
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            background-color: var(--color-background);
            color: var(--color-foreground-title);
        }
        .cyber-panel {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(6, 182, 212, 0.15);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
        }
        .cyber-glow-hover:hover {
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.4);
            border-color: rgba(6, 182, 212, 0.6);
        }
        .cyber-glow-emerald:hover {
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
            border-color: rgba(16, 185, 129, 0.6);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #030712;
        }
        ::-webkit-scrollbar-thumb {
            background: #1f2937;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #06b6d4;
        }
    </style>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
    
    <!-- Lucide Icons CDN (pinned to stable v0.439.0 to avoid Infinity conflict) -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.439.0/dist/umd/lucide.min.js" defer></script>
</head>
<body class="min-h-screen flex flex-col justify-between overflow-x-hidden selection:bg-cyan-500/30 selection:text-cyan-200">

    <!-- Header Navigation -->
    <header class="border-b border-cyan-500/10 bg-cyber-dark/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Logo Section -->
            <div class="flex items-center space-x-3">
                <div class="bg-cyan-500/10 p-2 rounded-lg border border-cyan-500/30 animate-pulse">
                    <i data-lucide="shield-alert" class="w-6 h-6 text-cyan-400"></i>
                </div>
                <div>
                    <span class="font-bold text-lg tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-emerald-400">
                        GSM GUARD
                    </span>
                    <span class="text-[10px] block text-cyan-500 font-mono tracking-widest uppercase">AI Safety System</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <?php if (\App\Core\Session::has('user') && \App\Core\Session::get('mfa_verified') === true): ?>
                <?php $currUser = \App\Core\Session::get('user'); ?>
                <nav class="flex items-center space-x-6">
                    <a href="<?= APP_URL ?>/dashboard" class="text-sm font-medium text-gray-300 hover:text-cyan-400 flex items-center space-x-1.5 transition-colors">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                        <span>Dashboard</span>
                    </a>
                    
                    <?php if ($currUser['role'] === 'admin'): ?>
                        <a href="<?= APP_URL ?>/admin" class="text-sm font-medium text-gray-300 hover:text-emerald-400 flex items-center space-x-1.5 transition-colors">
                            <i data-lucide="terminal" class="w-4 h-4"></i>
                            <span>Admin Panel</span>
                        </a>
                    <?php endif; ?>

                    <div class="border-l border-gray-800 h-6"></div>

                    <!-- User Badges -->
                    <div class="flex items-center space-x-3">
                        <div class="text-right hidden sm:block">
                            <span class="text-xs text-gray-400 block font-medium">Logged in as</span>
                            <span class="text-sm text-cyan-400 font-mono font-bold"><?= htmlspecialchars($currUser['username']) ?></span>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold tracking-wider uppercase <?= $currUser['role'] === 'admin' ? 'bg-rose-500/15 text-rose-400 border border-rose-500/30' : 'bg-cyan-500/15 text-cyan-400 border border-cyan-500/30' ?>">
                            <?= $currUser['role'] ?>
                        </span>
                        
                        <!-- Logout Action -->
                        <a href="<?= APP_URL ?>/logout" class="bg-gray-800 hover:bg-rose-500/20 hover:text-rose-400 p-2 rounded-lg border border-gray-700 transition-all" title="Logout">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                        </a>
                    </div>
                </nav>
            <?php else: ?>
                <div class="flex items-center space-x-4">
                    <a href="<?= APP_URL ?>/login" class="text-sm text-cyan-400 hover:text-cyan-300 transition-colors">Login</a>
                    <a href="<?= APP_URL ?>/register" class="bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 hover:bg-cyan-500/20 px-4 py-1.5 rounded-lg text-sm transition-all">
                        Register
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main View Content -->
    <main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?= $content ?>
    </main>

    <!-- Global Footer -->
    <footer class="border-t border-cyan-500/10 bg-cyber-dark/60 py-6 text-center text-xs text-gray-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center space-y-2 sm:space-y-0">
             <div>
                &copy; 2026 GSM Guard Corp. Built with secure message encryption.
            </div>
            <div class="flex space-x-4 font-mono text-[10px]">
                <span class="flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span><span class="text-emerald-400">Message Encryption Enabled</span></span>
                <span class="text-cyan-500">OpenSSL 1.1.1+</span>
                <span class="text-purple-400">AI safety check active</span>
            </div>
        </div>
    </footer>

    <!-- Initialize Lucide Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
