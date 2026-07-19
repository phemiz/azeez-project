<?php
namespace App\Core;

/**
 * Enterprise Reusable Dashboard Widget Engine
 * Generates beautiful, responsive, and animated HTML components matching 
 * the cyber-security system's dark-mode design variables.
 */
class Widget {
    
    /**
     * Renders a Statistics Dashboard Card.
     */
    public static function statCard(string $title, string $value, string $icon, string $color = 'cyan', ?string $subtitle = null): string {
        $iconColors = [
            'cyan' => 'text-cyan-400 bg-cyan-500/5 border-cyan-500/20',
            'emerald' => 'text-emerald-400 bg-emerald-500/5 border-emerald-500/20',
            'rose' => 'text-rose-500 bg-rose-500/5 border-rose-500/20',
            'amber' => 'text-amber-500 bg-amber-500/5 border-amber-500/20',
            'purple' => 'text-purple-400 bg-purple-500/5 border-purple-500/20'
        ];
        
        $colorClass = $iconColors[$color] ?? $iconColors['cyan'];
        $subHtml = $subtitle ? "<span class='text-3xs block mt-1.5 opacity-60 font-mono'>{$subtitle}</span>" : '';

        return "
        <div class='cyber-card flex items-center justify-between p-5 border rounded-2xl transition-all duration-200 hover:scale-[1.01]'>
            <div>
                <span class='text-2xs font-bold uppercase tracking-wider block' style='color: var(--color-foreground-muted);'>{$title}</span>
                <span class='text-3xl font-bold font-mono block mt-1' style='color: var(--color-foreground-title);'>{$value}</span>
                {$subHtml}
            </div>
            <div class='p-3 rounded-lg border {$colorClass}'>
                <i data-lucide='{$icon}' class='w-6 h-6'></i>
            </div>
        </div>
        ";
    }

    /**
     * Renders a Quick Actions Action Button Widget.
     */
    public static function quickAction(string $label, string $url, string $icon, string $color = 'cyan'): string {
        $colors = [
            'cyan' => 'border-cyan-500/30 hover:bg-cyan-500/10 text-cyan-400',
            'emerald' => 'border-emerald-500/30 hover:bg-emerald-500/10 text-emerald-400',
            'rose' => 'border-rose-500/30 hover:bg-rose-500/10 text-rose-500',
            'amber' => 'border-amber-500/30 hover:bg-amber-500/10 text-amber-500'
        ];
        $colorClass = $colors[$color] ?? $colors['cyan'];

        return "
        <a href='{$url}' class='flex items-center justify-between p-3.5 border rounded-xl font-mono text-2xs transition-all duration-150 hover:scale-[1.01] {$colorClass} bg-slate-900/40'>
            <div class='flex items-center gap-3'>
                <i data-lucide='{$icon}' class='w-4.5 h-4.5'></i>
                <span>{$label}</span>
            </div>
            <i data-lucide='arrow-right' class='w-4 h-4 opacity-50'></i>
        </a>
        ";
    }

    /**
     * Renders a Session Risk Score Dial Gauge.
     */
    public static function riskCard(int $score, string $recommendation, array $breakdown = []): string {
        $color = 'text-emerald-400';
        $bgColor = 'rgba(16, 185, 129, 0.05)';
        $borderColor = 'rgba(16, 185, 129, 0.2)';
        $label = 'SECURE';

        if ($score >= 70) {
            $color = 'text-rose-500 font-bold';
            $bgColor = 'rgba(239, 68, 68, 0.05)';
            $borderColor = 'rgba(239, 68, 68, 0.3)';
            $label = 'CRITICAL';
        } elseif ($score >= 30) {
            $color = 'text-amber-500';
            $bgColor = 'rgba(245, 158, 11, 0.05)';
            $borderColor = 'rgba(245, 158, 11, 0.2)';
            $label = 'SUSPICIOUS';
        }

        $breakdownHtml = '';
        if (!empty($breakdown)) {
            $breakdownHtml .= "
            <div class='mt-4 border-t pt-3' style='border-color: var(--color-border);'>
                <button onclick='toggleWidgetRiskMatrix()' class='text-3xs uppercase font-mono tracking-widest text-cyan-400 hover:underline flex items-center gap-1 cursor-pointer'>
                    <span>Inspect Risk Matrix</span>
                    <i data-lucide='chevron-down' class='w-3 h-3'></i>
                </button>
                <div id='widgetRiskMatrixBox' class='hidden mt-2.5 space-y-1.5 text-[10px] font-mono' style='color: var(--color-foreground-muted);'>";
            foreach ($breakdown as $key => $val) {
                $lbl = strtoupper(str_replace('_', ' ', $key));
                $breakdownHtml .= "<div class='flex justify-between'><span>{$lbl}:</span><span class='text-white font-bold'>{$val}%</span></div>";
            }
            $breakdownHtml .= "</div></div>";
        }

        return "
        <div class='cyber-card flex flex-col justify-between p-6 border rounded-2xl relative overflow-hidden' style='background-color: var(--color-surface);'>
            <div>
                <span class='text-2xs font-bold uppercase tracking-wider block' style='color: var(--color-foreground-muted);'>AI Risk Score</span>
                <div class='mt-3.5 flex items-baseline gap-2'>
                    <span class='text-4xl font-bold font-mono tracking-tight {$color}'>{$score}%</span>
                    <span class='text-xs uppercase font-mono font-bold' style='color: var(--color-foreground-muted);'>({$label})</span>
                </div>
            </div>
            
            {$breakdownHtml}

            <div class='text-2xs font-mono mt-4 px-3 py-2 border rounded' style='background-color: {$bgColor}; border-color: {$borderColor}; color: {$color};'>
                {$recommendation}
            </div>
        </div>
        
        <script>
        function toggleWidgetRiskMatrix() {
            const box = document.getElementById('widgetRiskMatrixBox');
            box.classList.toggle('hidden');
        }
        </script>
        ";
    }

    /**
     * Renders a Threat Intel Alert Card.
     */
    public static function threatCard(string $classification, int $score, string $ip, string $details, string $time): string {
        $color = $score >= 70 ? 'text-rose-500' : ($score >= 30 ? 'text-amber-500' : 'text-emerald-400');
        $border = $score >= 70 ? 'border-rose-500/25 bg-rose-500/5' : 'border-slate-800 bg-slate-900/20';

        return "
        <div class='p-4 border rounded-xl flex items-start gap-4 transition-all duration-150 {$border}'>
            <div class='p-2 rounded-lg bg-slate-950 border border-slate-850 {$color}'>
                <i data-lucide='shield-alert' class='w-4.5 h-4.5'></i>
            </div>
            <div class='space-y-1 flex-1 font-mono text-2xs'>
                <div class='flex justify-between items-center'>
                    <strong class='text-white uppercase font-sans text-xs'>{$classification}</strong>
                    <span class='{$color} font-bold'>Risk: {$score}%</span>
                </div>
                <p class='text-gray-400 leading-relaxed font-sans text-2xs mt-1'>{$details}</p>
                <div class='flex justify-between text-3xs pt-1.5 border-t border-slate-850' style='color: var(--color-foreground-muted);'>
                    <span>Source Node: {$ip}</span>
                    <span>{$time}</span>
                </div>
            </div>
        </div>
        ";
    }

    /**
     * Renders a Notification Row.
     */
    public static function notificationRow(string $title, string $msg, string $type, string $time, bool $isRead = false): string {
        $iconMap = [
            'success'  => ['shield-check', 'text-emerald-400 bg-emerald-500/5 border-emerald-500/20'],
            'warning'  => ['alert-triangle', 'text-amber-500 bg-amber-500/5 border-amber-500/20'],
            'error'    => ['shield-x', 'text-red-400 bg-red-500/5 border-red-500/20'],
            'security' => ['shield-alert', 'text-rose-500 bg-rose-500/5 border-rose-500/30 font-bold']
        ];
        
        $item = $iconMap[$type] ?? ['bell', 'text-cyan-400 bg-cyan-500/5 border-cyan-500/20'];
        $icon = $item[0];
        $colorClass = $item[1];

        return "
        <div class='p-4 border rounded-xl flex items-start justify-between gap-4 transition-all duration-150 bg-slate-900/40 border-slate-850 hover:border-slate-800 {$colorClass}'>
            <div class='flex items-start gap-3'>
                <div class='p-2 rounded border border-current'>
                    <i data-lucide='{$icon}' class='w-4.5 h-4.5'></i>
                </div>
                <div class='space-y-0.5'>
                    <h4 class='text-xs font-bold text-white font-mono uppercase tracking-wide flex items-center gap-1.5'>
                        <span>{$title}</span>
                        " . (!$isRead ? "<span class='w-1.5 h-1.5 rounded-full bg-cyan-500 animate-ping'></span>" : "") . "
                    </h4>
                    <p class='text-2xs leading-relaxed text-gray-300 font-sans'>{$msg}</p>
                    <span class='text-3xs block font-mono opacity-65'>{$time}</span>
                </div>
            </div>
        </div>
        ";
    }

    /**
     * Renders a Recent Activities Timeline List item.
     */
    public static function activityItem(string $action, string $ip, string $time, string $icon = 'server'): string {
        return "
        <div class='flex items-start gap-4 text-xs font-mono relative pl-6 before:absolute before:left-2 before:top-2 before:bottom-0 before:w-[1px] before:bg-slate-800 last:before:hidden'>
            <div class='absolute left-0 top-1 p-1 bg-slate-900 border border-slate-800 text-cyan-400 rounded-full'>
                <i data-lucide='{$icon}' class='w-3 h-3'></i>
            </div>
            <div class='space-y-0.5 flex-1'>
                <div class='flex justify-between items-center text-3xs' style='color: var(--color-foreground-muted);'>
                    <span>{$ip}</span>
                    <span>{$time}</span>
                </div>
                <span class='text-white font-semibold block text-2xs'>{$action}</span>
            </div>
        </div>
        ";
    }

    /**
     * Renders a Reusable Data Table.
     */
    public static function table(string $title, array $headers, array $rows, string $icon = 'table'): string {
        $headerHtml = '';
        foreach ($headers as $h) {
            $headerHtml .= "<th class='pb-2.5 uppercase'>" . htmlspecialchars($h) . "</th>";
        }

        $rowsHtml = '';
        if (empty($rows)) {
            $colsCount = count($headers);
            $rowsHtml .= "<tr><td colspan='{$colsCount}' class='py-4 text-center text-gray-500 font-mono'>No records complied.</td></tr>";
        } else {
            foreach ($rows as $row) {
                $rowsHtml .= "<tr class='hover:bg-slate-800/10 transition-colors text-gray-300 border-b border-slate-900 last:border-b-0'>";
                foreach ($row as $val) {
                    $rowsHtml .= "<td class='py-3 font-mono text-[11px]'>" . htmlspecialchars($val ?? '') . "</td>";
                }
                $rowsHtml .= "</tr>";
            }
        }

        return "
        <div class='cyber-card p-6 space-y-4'>
            <h3 class='text-xs font-bold uppercase tracking-wider text-white flex items-center gap-2 font-mono border-b pb-3' style='border-color: var(--color-border);'>
                <i data-lucide='{$icon}' class='w-4.5 h-4.5' style='color: var(--color-primary);'></i>
                <span>{$title}</span>
            </h3>
            <div class='overflow-x-auto'>
                <table class='w-full text-left text-xs border-collapse'>
                    <thead>
                        <tr class='border-b font-mono text-[10px]' style='border-color: var(--color-border); color: var(--color-primary);'>
                            {$headerHtml}
                        </tr>
                    </thead>
                    <tbody class='divide-y divide-slate-850'>
                        {$rowsHtml}
                    </tbody>
                </table>
            </div>
        </div>
        ";
    }

    /**
     * Renders a Reusable Chart container.
     */
    public static function chartContainer(string $id, string $title, string $icon = 'bar-chart-3'): string {
        return "
        <div class='cyber-card p-6 space-y-4'>
            <h3 class='text-xs font-bold uppercase tracking-wider text-white flex items-center gap-2 font-mono' style='color: var(--color-foreground-title);'>
                <i data-lucide='{$icon}' class='w-4.5 h-4.5' style='color: var(--color-primary);'></i>
                <span>{$title}</span>
            </h3>
            <div class='h-48'>
                <canvas id='{$id}'></canvas>
            </div>
        </div>
        ";
    }
}
