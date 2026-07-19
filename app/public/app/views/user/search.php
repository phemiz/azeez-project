<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}
?>
<div class="space-y-6 max-w-5xl mx-auto animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-800" style="border-color: var(--color-border);">
        <div>
            <h1 class="text-xl font-bold text-white font-mono uppercase">SIEM Global Search</h1>
            <span class="text-xs" style="color: var(--color-foreground-muted);">Unified lookup index across user registers, security alerts, and cipher envelopes</span>
        </div>
        <a href="<?= APP_URL ?>/dashboard" class="btn-secondary text-xs py-1.5 px-3">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Return to Workspace</span>
        </a>
    </div>

    <!-- Search Input & Autocomplete Box -->
    <div class="space-y-4">
        <form method="GET" action="<?= APP_URL ?>/search" class="relative">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center" style="color: var(--color-foreground-muted);">
                    <i data-lucide="search" class="w-5 h-5"></i>
                </span>
                <input type="text" id="globalSearchInput" name="q" value="<?= htmlspecialchars($query) ?>" autocomplete="off"
                       placeholder="Enter keywords, IP addresses, usernames, or recipients..." 
                       class="cyber-input pl-12 pr-24 py-3.5 text-sm w-full focus:border-cyan-500 shadow-lg shadow-slate-950/20" />
                
                <!-- Target Filter Inside Input -->
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center gap-2">
                    <select name="target" class="bg-slate-900 border border-slate-800 text-gray-400 text-3xs font-mono py-1.5 px-2 rounded-lg focus:outline-none">
                        <option value="">All Indexes</option>
                        <option value="users" <?= $target === 'users' ? 'selected' : '' ?>>Operators</option>
                        <option value="logs" <?= $target === 'logs' ? 'selected' : '' ?>>Audits</option>
                        <option value="messages" <?= $target === 'messages' ? 'selected' : '' ?>>Envelopes</option>
                        <option value="alerts" <?= $target === 'alerts' ? 'selected' : '' ?>>Alarms</option>
                    </select>
                </div>
            </div>

            <!-- Autocomplete suggestions dropdown -->
            <div id="autocompleteDropdown" class="hidden absolute left-0 right-0 mt-1.5 bg-slate-900 border border-slate-800 rounded-xl shadow-2xl z-50 overflow-hidden font-mono text-2xs divide-y divide-slate-850">
            </div>
        </form>

        <!-- Search History Tags -->
        <?php if (!empty($history)): ?>
            <div class="flex flex-wrap items-center gap-2 text-2xs font-mono">
                <span style="color: var(--color-foreground-muted);">Recent Searches:</span>
                <?php foreach ($history as $h): ?>
                    <a href="?q=<?= urlencode($h) ?>" class="py-1 px-3 border border-slate-800 hover:border-cyan-500 rounded bg-slate-900/40 text-gray-300 transition-all">
                        <?= htmlspecialchars($h) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Search Results Classified Catalog -->
    <?php if (!empty($query)): ?>
        <div class="space-y-8">
            
            <!-- 1. Operators Matches -->
            <?php if (isset($results['users']) && !empty($results['users'])): ?>
                <div class="cyber-card p-6 space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase font-mono flex items-center gap-1.5 border-b pb-2" style="border-color: var(--color-border);">
                        <i data-lucide="users" class="w-4 h-4 text-cyan-400"></i>
                        <span>Matched Operator Profiles</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-2xs border-collapse">
                            <thead>
                                <tr class="border-b" style="border-color: var(--color-border); color: var(--color-primary);">
                                    <th class="pb-2">Username</th>
                                    <th class="pb-2">Email</th>
                                    <th class="pb-2">Secure Phone</th>
                                    <th class="pb-2">Status</th>
                                    <th class="pb-2">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['users'] as $u): ?>
                                    <tr class="hover:bg-slate-800/10 transition-colors text-gray-300 font-mono">
                                        <td class="py-2.5 font-bold text-white"><?= $searchEngine->highlight($u['username'], $query) ?></td>
                                        <td class="py-2.5"><?= $searchEngine->highlight($u['email'], $query) ?></td>
                                        <td class="py-2.5"><?= $searchEngine->highlight($u['phone'], $query) ?></td>
                                        <td class="py-2.5">
                                            <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-cyan-500/10 text-cyan-400"><?= strtoupper($u['status']) ?></span>
                                        </td>
                                        <td class="py-2.5" style="color: var(--color-foreground-muted);"><?= $u['created_at'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 2. Audits & Transaction Logs Matches -->
            <?php if (isset($results['logs']) && !empty($results['logs'])): ?>
                <div class="cyber-card p-6 space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase font-mono flex items-center gap-1.5 border-b pb-2" style="border-color: var(--color-border);">
                        <i data-lucide="file-check-2" class="w-4 h-4 text-cyan-400"></i>
                        <span>Matched Audit & Activity Ledger Logs</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-2xs border-collapse">
                            <thead>
                                <tr class="border-b" style="border-color: var(--color-border); color: var(--color-primary);">
                                    <th class="pb-2">Timestamp</th>
                                    <th class="pb-2">Operator</th>
                                    <th class="pb-2">Event</th>
                                    <th class="pb-2">IP Address</th>
                                    <th class="pb-2">Risk</th>
                                    <th class="pb-2">Classification</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['logs'] as $l): ?>
                                    <tr class="hover:bg-slate-800/10 transition-colors text-gray-300 font-mono">
                                        <td class="py-2.5" style="color: var(--color-foreground-muted);"><?= $l['created_at'] ?></td>
                                        <td class="py-2.5 font-bold text-white"><?= $searchEngine->highlight($l['username'] ?? 'SYSTEM', $query) ?></td>
                                        <td class="py-2.5"><?= $searchEngine->highlight($l['action'], $query) ?></td>
                                        <td class="py-2.5"><?= $searchEngine->highlight($l['ip_address'], $query) ?></td>
                                        <td class="py-2.5 font-bold <?= $l['risk_score'] >= 70 ? 'text-rose-500' : ($l['risk_score'] >= 30 ? 'text-amber-500' : 'text-emerald-400') ?>"><?= $l['risk_score'] ?>%</td>
                                        <td class="py-2.5"><?= $searchEngine->highlight($l['threat_classification'], $query) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 3. Envelopes Matches -->
            <?php if (isset($results['messages']) && !empty($results['messages'])): ?>
                <div class="cyber-card p-6 space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase font-mono flex items-center gap-1.5 border-b pb-2" style="border-color: var(--color-border);">
                        <i data-lucide="mail" class="w-4 h-4 text-cyan-400"></i>
                        <span>Matched Encrypted Cipher Envelopes</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-2xs border-collapse">
                            <thead>
                                <tr class="border-b" style="border-color: var(--color-border); color: var(--color-primary);">
                                    <th class="pb-2">Recipient Carrier</th>
                                    <th class="pb-2">Cipher Mode</th>
                                    <th class="pb-2 text-center">Threat Risk Grade</th>
                                    <th class="pb-2">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['messages'] as $m): ?>
                                    <tr class="hover:bg-slate-800/10 transition-colors text-gray-300 font-mono">
                                        <td class="py-2.5 font-bold text-white"><?= $searchEngine->highlight($m['recipient'], $query) ?></td>
                                        <td class="py-2.5"><?= $searchEngine->highlight($m['algorithm'], $query) ?></td>
                                        <td class="py-2.5 text-center">
                                            <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-red-500/10 text-rose-400"><?= htmlspecialchars(strtoupper($m['risk_grade'])) ?></span>
                                        </td>
                                        <td class="py-2.5" style="color: var(--color-foreground-muted);"><?= $m['created_at'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 4. Security Alerts Matches -->
            <?php if (isset($results['alerts']) && !empty($results['alerts'])): ?>
                <div class="cyber-card p-6 space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase font-mono flex items-center gap-1.5 border-b pb-2" style="border-color: var(--color-border);">
                        <i data-lucide="shield-alert" class="w-4 h-4 text-cyan-400"></i>
                        <span>Matched Heuristic Alarm Alerts</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-2xs border-collapse">
                            <thead>
                                <tr class="border-b" style="border-color: var(--color-border); color: var(--color-primary);">
                                    <th class="pb-2">Timestamp</th>
                                    <th class="pb-2">Severity</th>
                                    <th class="pb-2">Alert Warning message details</th>
                                    <th class="pb-2">Resolve Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['alerts'] as $a): ?>
                                    <tr class="hover:bg-slate-800/10 transition-colors text-gray-300 font-mono">
                                        <td class="py-2.5" style="color: var(--color-foreground-muted);"><?= $a['created_at'] ?></td>
                                        <td class="py-2.5">
                                            <span class="px-2 py-0.5 rounded text-[8px] font-bold <?= $a['severity'] === 'critical' ? 'bg-red-500/10 text-red-500 animate-pulse' : 'bg-amber-500/10 text-amber-500' ?>"><?= strtoupper($a['severity']) ?></span>
                                        </td>
                                        <td class="py-2.5 text-white"><?= $searchEngine->highlight($a['message'], $query) ?></td>
                                        <td class="py-2.5 font-bold uppercase text-2xs"><?= htmlspecialchars($a['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    <?php elseif (!empty($query)): ?>
        <div class="cyber-card p-8 text-center text-xs font-mono" style="color: var(--color-foreground-muted);">
            Zero results found across all active index libraries.
        </div>
    <?php endif; ?>
</div>

<script>
const searchInput = document.getElementById('globalSearchInput');
const dropdown = document.getElementById('autocompleteDropdown');

searchInput.addEventListener('input', async function() {
    const q = this.value.trim();
    if (q.length < 2) {
        dropdown.innerHTML = '';
        dropdown.classList.add('hidden');
        return;
    }

    try {
        const response = await fetch('<?= APP_URL ?>/api/search/live?q=' + encodeURIComponent(q));
        const data = await response.json();

        if (response.ok && data.status === 'success' && data.results.length > 0) {
            dropdown.innerHTML = data.results.map(r => `
                <a href="${r.url}" class="block p-3 hover:bg-slate-800/50 hover:text-cyan-400 transition-colors border-b border-slate-850 last:border-b-0 text-white font-mono flex justify-between items-center">
                    <span>${r.label}</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 opacity-60"></i>
                </a>
            `).join('');
            dropdown.classList.remove('hidden');
            lucide.createIcons();
        } else {
            dropdown.innerHTML = '';
            dropdown.classList.add('hidden');
        }
    } catch(e) {
        // Suppress autocomplete error
    }
});

// Close dropdown clicking outside
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});
</script>
