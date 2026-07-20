<?php
// Safety Check: prevent direct access
if (!defined('ENTRY_SECURE') && count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Generate CSRF token
$csrfToken = \App\Core\Session::generateCSRFToken();

// Compute dynamic operator security score
$securityScore = 100;
$securityScore -= ($alertCount * 5); // deduct 5% per alert
$securityScore -= round($maxRiskScore * 0.4); // deduct risk scale
$securityScore = max(35, min(100, $securityScore));
?>
<div class="space-y-8">
    <!-- Welcome Header & Security Status Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Operator Welcome -->
        <div class="cyber-card col-span-2 flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -top-24 -left-24 w-48 h-48 rounded-full bg-cyan-500/5 blur-3xl pointer-events-none"></div>
            <div>
                <h1 class="text-xl font-bold text-white flex items-center gap-2 font-mono uppercase">
                    <span>Welcome,</span>
                    <span style="color: var(--color-primary);"><?= htmlspecialchars($user['username']) ?></span>
                </h1>
                <p class="text-xs mt-1" style="color: var(--color-foreground-muted);">
                    Status: Active &middot; System is secure and running
                </p>
            </div>
            
            <!-- Dynamic Last Login details -->
            <div class="mt-6 pt-4 border-t text-2xs font-mono" style="border-color: var(--color-border); color: var(--color-foreground-muted);">
                <?php if ($lastLogin): ?>
                    LAST LOGGED IN FROM IP: <span class="text-white"><?= htmlspecialchars($lastLogin['ip_address']) ?></span> AT <span class="text-white"><?= date('Y-m-d H:i:s', strtotime($lastLogin['created_at'])) ?></span>
                <?php else: ?>
                    STATE: First time logging in. Security settings are ready.
                <?php endif; ?>
            </div>
        </div>

        <!-- Active Session Risk rating gauge -->
        <div class="cyber-card flex flex-col justify-between" title="A score showing how safe your current session is. Higher percentages mean higher threat risk." style="background-color: var(--color-surface);">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Threat Level Score</span>
                <?php 
                    $risk = $riskAssessment['risk_score'];
                    $color = 'text-emerald-400';
                    $bgColor = 'rgba(0, 255, 65, 0.05)';
                    $borderColor = 'rgba(0, 255, 65, 0.2)';
                    if ($risk >= 70) {
                        $color = 'text-red-500';
                        $bgColor = 'rgba(255, 51, 51, 0.05)';
                        $borderColor = 'rgba(255, 51, 51, 0.3)';
                    } elseif ($risk >= 30) {
                        $color = 'text-amber-500';
                        $bgColor = 'rgba(245, 158, 11, 0.05)';
                        $borderColor = 'rgba(245, 158, 11, 0.2)';
                    }
                ?>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-4xl font-bold font-mono tracking-tight <?= $color ?>"><?= $risk ?>%</span>
                    <span class="text-xs uppercase font-mono font-bold" style="color: var(--color-foreground-muted);">
                        (<?= $risk >= 70 ? 'HIGH RISK' : ($risk >= 30 ? 'SUSPICIOUS' : 'SECURE') ?>)
                    </span>
                </div>
            </div>
            
            <!-- Risk Breakdown Trigger -->
            <div class="mt-4 border-t pt-2" style="border-color: var(--color-border);">
                <button onclick="toggleRiskDetails()" title="Click to see the breakdown of what is affecting your security score." class="text-3xs uppercase font-mono tracking-widest text-cyan-400 hover:underline flex items-center gap-1 cursor-pointer">
                    <span>Show Threat Level Details</span>
                    <i data-lucide="chevron-down" class="w-3 h-3"></i>
                </button>
                <div id="riskDetailsBox" class="hidden mt-2 space-y-1 text-[10px] font-mono" style="color: var(--color-foreground-muted);">
                    <div class="flex justify-between"><span>FAILED LOGINS:</span><span class="text-white font-bold"><?= $riskAssessment['breakdown']['failed_logins'] ?>%</span></div>
                    <div class="flex justify-between"><span>UNKNOWN DEVICE:</span><span class="text-white font-bold"><?= $riskAssessment['breakdown']['unknown_device'] ?>%</span></div>
                    <div class="flex justify-between"><span>UNKNOWN BROWSER:</span><span class="text-white font-bold"><?= $riskAssessment['breakdown']['unknown_browser'] ?>%</span></div>
                    <div class="flex justify-between"><span>UNKNOWN IP ADDRESS:</span><span class="text-white font-bold"><?= $riskAssessment['breakdown']['unknown_ip'] ?>%</span></div>
                    <div class="flex justify-between"><span>FAST LOGINS:</span><span class="text-white font-bold"><?= $riskAssessment['breakdown']['rapid_logins'] ?>%</span></div>
                    <div class="flex justify-between"><span>LOCATION JUMP:</span><span class="text-white font-bold"><?= $riskAssessment['breakdown']['impossible_travel'] ?>%</span></div>
                    <div class="flex justify-between"><span>DATA TRANSFER VOLUME:</span><span class="text-white font-bold"><?= $riskAssessment['breakdown']['encryption_frequency'] ?>%</span></div>
                    <div class="flex justify-between"><span>UNUSUAL SESSION BEHAVIOR:</span><span class="text-white font-bold"><?= $riskAssessment['breakdown']['session_anomaly'] ?>%</span></div>
                </div>
            </div>
            
            <div class="text-2xs font-mono mt-4 px-3 py-1 border rounded" style="background-color: <?= $bgColor ?>; border-color: <?= $borderColor ?>; color: <?= $risk >= 30 ? 'var(--color-accent)' : 'var(--color-primary)' ?>;">
                <?= htmlspecialchars($riskAssessment['recommendations']) ?>
            </div>
        </div>
    </div>

    <!-- Security Telemetry Statistics Row -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6">
        <!-- Total Envelopes -->
        <div class="cyber-card flex items-center justify-between" title="The total number of messages you have locked using this platform.">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Encrypted Messages</span>
                <span class="text-3xl font-bold font-mono block mt-1" style="color: var(--color-foreground-title);"><?= $totalEncrypted ?></span>
            </div>
            <div class="p-3 rounded-lg" style="background-color: rgba(0, 255, 65, 0.05); border: 1px solid rgba(0, 255, 65, 0.2);">
                <i data-lucide="mail-check" class="w-6 h-6" style="color: var(--color-primary);"></i>
            </div>
        </div>

        <!-- Security status score -->
        <div class="cyber-card flex items-center justify-between" title="Your overall security score out of 100. Lower scores mean your session might be unsafe.">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Security Score</span>
                <span class="text-3xl font-bold font-mono block mt-1" style="color: var(--color-primary);"><?= $securityScore ?>/100</span>
            </div>
            <div class="p-3 rounded-lg" style="background-color: rgba(0, 255, 65, 0.05); border: 1px solid rgba(0, 255, 65, 0.2);">
                <i data-lucide="shield-check" class="w-6 h-6" style="color: var(--color-primary);"></i>
            </div>
        </div>

        <!-- Profile Completion -->
        <div class="cyber-card flex items-center justify-between" title="How complete your user profile details are. Currently fully completed.">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">User Profile</span>
                <span class="text-3xl font-bold font-mono block mt-1" style="color: var(--color-foreground-title);">100%</span>
            </div>
            <div class="p-3 rounded-lg" style="background-color: rgba(0, 255, 65, 0.05); border: 1px solid rgba(0, 255, 65, 0.2);">
                <i data-lucide="user-check" class="w-6 h-6" style="color: var(--color-primary);"></i>
            </div>
        </div>

        <!-- Alert Log Count -->
        <div class="cyber-card flex items-center justify-between" title="The number of active security alarms or threats detected on your account.">
            <div>
                <span class="text-2xs font-bold uppercase tracking-wider block" style="color: var(--color-foreground-muted);">Security Alerts</span>
                <span class="text-3xl font-bold font-mono block mt-1" style="color: var(--color-accent);"><?= $alertCount ?></span>
            </div>
            <div class="p-3 rounded-lg" style="background-color: rgba(255, 51, 51, 0.05); border: 1px solid rgba(255, 51, 51, 0.2);">
                <i data-lucide="bell" class="w-6 h-6" style="color: var(--color-accent);"></i>
            </div>
        </div>
    </div>

    <!-- Chart Telemetry Graphic (Visual representation of anomalies) -->
    <div class="cyber-card p-6" title="A chart showing your security risk history over your last few login sessions.">
        <h3 class="text-sm font-bold uppercase tracking-wider text-white mb-4 font-mono">Security Threat History</h3>
        <div class="h-64">
            <canvas id="threatHistoryChart"></canvas>
        </div>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Left Panel: Cryptographic Actions Portal -->
        <div class="space-y-6">
            <!-- Tabs selection -->
            <div class="flex p-1.5 rounded-xl border" style="background-color: var(--color-surface); border-color: var(--color-border);">
                <button onclick="switchTab('encrypt')" id="btn-tab-encrypt" title="Click to switch to the screen where you can encrypt your messages."
                        class="flex-1 py-2 rounded-lg text-xs font-mono font-bold uppercase transition-all flex items-center justify-center gap-2 bg-indigo-600 text-white border border-indigo-600">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                    <span>Encrypt Message</span>
                </button>
                <button onclick="switchTab('decrypt')" id="btn-tab-decrypt" title="Click to switch to the screen where you can decrypt messages you received."
                        class="flex-1 py-2 rounded-lg text-xs font-mono font-bold uppercase transition-all flex items-center justify-center gap-2 bg-cyan-50 text-cyan-700 border border-cyan-100 hover:bg-cyan-100">
                    <i data-lucide="unlock" class="w-4 h-4"></i>
                    <span>Decrypt Message</span>
                </button>
            </div>

            <!-- ENCRYPT PANEL -->
            <div id="panel-encrypt" class="cyber-card space-y-4">
                <h2 class="text-sm font-bold text-white flex items-center gap-2 font-mono uppercase">
                    <i data-lucide="shield-check" style="color: var(--color-primary);" class="w-5 h-5"></i>
                    <span>Encrypt Message</span>
                </h2>
                
                <form id="encryptForm" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Recipient Phone Number</label>
                            <input type="text" name="recipient" required placeholder="+2348030000000" title="Enter the phone number of the person who will receive this message." class="cyber-input font-mono" />
                        </div>
                        <div>
                            <label class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Secret Key Password</label>
                            <input type="password" name="passphrase" required placeholder="Shared secret passphrase" title="Enter a password that you and the receiver both know to encrypt this message." class="cyber-input" />
                        </div>
                    </div>

                    <!-- Custom GSM simulation inputs (To trigger AI warnings) -->
                    <div class="p-4 rounded-xl border space-y-3" style="background-color: rgba(0,0,0,0.15); border-color: var(--color-border);">
                        <span class="text-2xs text-gray-400 block font-mono font-bold tracking-widest uppercase">Mobile Network Settings</span>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-2xs font-semibold block mb-1" style="color: var(--color-primary);">Network Center Address</label>
                                <input type="text" name="smsc" value="234803000000" placeholder="e.g. +234803..." title="The address of the phone network routing station. Keep the default unless testing."
                                       class="cyber-input font-mono text-xs" />
                                <span class="text-[8px]" style="color: var(--color-foreground-muted);">Use '0000000000' to test fake cell tower alerts</span>
                            </div>
                            <div>
                                <label class="text-2xs font-semibold block mb-1" style="color: var(--color-primary);">Message Type</label>
                                <select name="protocol_id" class="cyber-input text-xs" title="Choose standard text message or a hidden message to test fake base stations.">
                                    <option value="0">Standard Text Message</option>
                                    <option value="64">Hidden Text Message (Silent Tracking)</option>
                                </select>
                                <span class="text-[8px]" style="color: var(--color-foreground-muted);">Hidden messages show fake tracking alerts</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Message Text</label>
                        <textarea name="message" rows="3" required placeholder="Type your message here..." title="Type the message you want to encrypt." class="cyber-input"></textarea>
                    </div>

                    <button type="submit" id="btn-encrypt-submit" title="Click to encrypt your message using your password." class="btn-primary w-full justify-center">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                        <span>Encrypt Message</span>
                    </button>
                </form>

                <!-- Result panel -->
                <div id="encryptResult" class="hidden p-4 rounded-xl border space-y-3" style="background-color: rgba(0,0,0,0.1); border-color: var(--color-primary);">
                    <span class="text-xs font-mono font-bold flex items-center gap-1.5" style="color: var(--color-primary);">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        <span>Message Encrypted Successfully</span>
                    </span>
                    
                    <div class="space-y-2">
                        <label class="text-[10px] block font-mono" style="color: var(--color-foreground-muted);">Encrypted Message (Ciphertext):</label>
                        <textarea id="output-ciphertext" readonly class="w-full text-xs bg-black/40 border border-slate-800 rounded-lg p-2 font-mono h-16 focus:outline-none" style="color: var(--color-primary);"></textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-[10px] font-mono">
                        <div>
                            <span style="color: var(--color-foreground-muted);" class="block">Security Key (IV):</span>
                            <input id="output-iv" type="text" readonly class="w-full bg-black/40 border border-slate-800 rounded p-1 text-[10px] focus:outline-none" style="color: var(--color-primary);" />
                        </div>
                        <div>
                            <span style="color: var(--color-foreground-muted);" class="block">Security Salt:</span>
                            <input id="output-salt" type="text" readonly class="w-full bg-black/40 border border-slate-800 rounded p-1 text-[10px] focus:outline-none" style="color: var(--color-primary);" />
                        </div>
                        <div>
                            <span style="color: var(--color-foreground-muted);" class="block">Security Signature:</span>
                            <input id="output-signature" type="text" readonly class="w-full bg-black/40 border border-slate-800 rounded p-1 text-[10px] focus:outline-none" style="color: var(--color-primary);" />
                        </div>
                    </div>

                    <button onclick="copyCredentials()" title="Copy all the encrypted message details to your clipboard so you can paste and send them." class="btn-secondary w-full justify-center">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        <span>Copy Encryption Details</span>
                    </button>
                </div>
            </div>

            <!-- DECRYPT PANEL -->
            <div id="panel-decrypt" class="cyber-card space-y-4 hidden">
                <h2 class="text-sm font-bold text-white flex items-center gap-2 font-mono uppercase">
                    <i data-lucide="shield-question" style="color: var(--color-primary);" class="w-5 h-5"></i>
                    <span>Decrypt Message</span>
                </h2>
                
                <form id="decryptForm" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div>
                        <label class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Select Pre-configured Key & Salt Envelope</label>
                        <select id="decryptEnvelopeSelector" onchange="selectDecryptEnvelope(this.value)" class="cyber-input py-2 text-xs font-mono" style="background-color: var(--color-surface); border-color: var(--color-border); color: var(--color-foreground-title);">
                            <option value="">-- Enter Key & Salt Manually --</option>
                            <?php foreach ($messages as $msg): ?>
                                <?php 
                                    $label = ($msg['sender_id'] == $user['id']) ? "Sent to " . $msg['recipient'] : "Rcvd from " . ($msg['sender_username'] ?? 'System');
                                    $label .= " (" . date('H:i m-d', strtotime($msg['created_at'])) . ") - " . substr($msg['iv'], 0, 8) . "...";
                                ?>
                                <option value="<?= htmlspecialchars(json_encode([
                                    'ciphertext' => $msg['encrypted_payload'],
                                    'iv'         => $msg['iv'],
                                    'salt'       => $msg['salt'],
                                    'signature'  => $msg['signature']
                                ])) ?>">
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Encrypted Message Text</label>
                        <textarea name="ciphertext" rows="2" required placeholder="Paste encrypted message here" title="Paste the encrypted message text (ciphertext) you received here." class="cyber-input font-mono text-xs"></textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="text-[10px] font-mono font-semibold" style="color: var(--color-primary);">Security Key (IV)</label>
                            <input type="text" name="iv" required placeholder="Base64 IV" title="Enter the Security Key (IV) code associated with the encrypted message." class="cyber-input font-mono text-xs" />
                        </div>
                        <div>
                            <label class="text-[10px] font-mono font-semibold" style="color: var(--color-primary);">Security Salt</label>
                            <input type="text" name="salt" required placeholder="Base64 Salt" title="Enter the Security Salt code associated with the encrypted message." class="cyber-input font-mono text-xs" />
                        </div>
                        <div>
                            <label class="text-[10px] font-mono font-semibold" style="color: var(--color-primary);">Security Signature</label>
                            <input type="text" name="signature" required placeholder="HMAC Signature" title="Enter the Security Signature code associated with the encrypted message." class="cyber-input font-mono text-xs" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-2xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--color-primary);">Decryption Password</label>
                        <input type="password" name="passphrase" required placeholder="Shared secret password" title="Enter the password used to encrypt this message to decrypt it." class="cyber-input" />
                    </div>

                    <button type="submit" id="btn-decrypt-submit" title="Click to decrypt the message and see the original text." class="btn-primary w-full justify-center">
                        <i data-lucide="unlock" class="w-4 h-4"></i>
                        <span>Decrypt Message</span>
                    </button>
                </form>

                <!-- Decrypt Result panel -->
                <div id="decryptResult" class="hidden p-4 rounded-xl border space-y-2" style="background-color: rgba(0,0,0,0.1); border-color: var(--color-primary);">
                    <span class="text-xs font-mono font-bold flex items-center gap-1.5" style="color: var(--color-primary);">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        <span>Message Decrypted Successfully</span>
                    </span>
                    <div class="bg-black/40 p-3 rounded-lg border border-slate-800 text-xs text-white font-mono break-all whitespace-pre-wrap" id="output-plaintext"></div>
                </div>

                <!-- Available Envelopes to Decrypt -->
                <div class="border-t border-slate-800 pt-4 mt-4" style="border-color: var(--color-border);">
                    <label class="block text-2xs font-bold uppercase tracking-wider mb-2" style="color: var(--color-primary);">Available Envelopes Ledger</label>
                    <span class="text-[10px] block mb-2" style="color: var(--color-foreground-muted);">Click an envelope below to load its parameters automatically:</span>
                    
                    <div class="space-y-2 max-h-[160px] overflow-y-auto pr-1">
                        <?php if (empty($messages)): ?>
                            <div class="text-center py-4 text-xs font-mono" style="color: var(--color-foreground-muted);">No envelopes found.</div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <div onclick="populateDecryptEnvelope('<?= htmlspecialchars(json_encode([
                                    'ciphertext' => $msg['encrypted_payload'],
                                    'iv'         => $msg['iv'],
                                    'salt'       => $msg['salt'],
                                    'signature'  => $msg['signature']
                                ])) ?>')"
                                     class="p-2.5 rounded-lg border border-slate-800 hover:border-cyan-500/50 hover:bg-cyan-500/5 cursor-pointer transition-all duration-150 font-mono text-[10px]"
                                     style="border-color: var(--color-border); background-color: rgba(0,0,0,0.15);">
                                    <div class="flex justify-between items-center text-white mb-1">
                                        <?php if ($msg['sender_id'] == $user['id']): ?>
                                            <span class="font-bold text-cyan-400">SENT TO: <?= htmlspecialchars($msg['recipient']) ?></span>
                                        <?php else: ?>
                                            <span class="font-bold text-emerald-400">RCVD FROM: <?= htmlspecialchars($msg['sender_username'] ?? 'System') ?></span>
                                        <?php endif; ?>
                                        <span class="opacity-60 text-[9px]"><?= date('H:i m-d', strtotime($msg['created_at'])) ?></span>
                                    </div>
                                    <div class="truncate text-gray-400"><?= substr($msg['encrypted_payload'], 0, 35) ?>...</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Activity Logs & AI Reports -->
        <div class="space-y-6">
            <!-- AI Recommendations Card (Dynamic) -->
            <div id="aiWarningCard" class="cyber-card bg-cyan-50 space-y-3 relative overflow-hidden border border-cyan-200/50" title="Tips and analysis from our system checking for network trackers and cell anomalies.">
                <!-- Overlay glow -->
                <div class="absolute top-0 right-0 w-24 h-24 bg-cyan-500/5 rounded-full blur-xl pointer-events-none"></div>

                <div class="flex items-center gap-2">
                    <div class="bg-cyan-100 p-2 rounded-lg border border-cyan-300">
                        <i data-lucide="brain-circuit" class="w-5 h-5 text-cyan-600"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider font-mono" style="color: var(--color-foreground-title);">AI Safety Tips</h3>
                        <span class="text-[10px] block" style="color: var(--color-foreground-muted);">Real-time threat checking</span>
                    </div>
                </div>

                <div class="space-y-2" id="aiReportBox">
                    <p class="text-xs leading-relaxed font-mono" style="color: var(--color-foreground-title);">
                        State: Normal. Network checks show no fake cell towers or active silent tracking messages. Messages locked in this session are safe.
                    </p>
                </div>
            </div>

            <!-- Active Security Alarms Feed -->
            <div class="cyber-card space-y-4" title="Active alerts and warnings about your account security or network status.">
                <h3 class="text-xs font-bold uppercase tracking-wider flex items-center justify-between font-mono" style="color: var(--color-foreground-title);">
                    <span>Security Alerts</span>
                    <i data-lucide="shield-alert" class="w-4 h-4 text-rose-500 animate-pulse"></i>
                </h3>

                <div class="space-y-3 max-h-[250px] overflow-y-auto pr-1">
                    <?php if (empty($alerts)): ?>
                        <div class="text-center py-4 text-xs font-mono" style="color: var(--color-foreground-muted);">No active threat alarms detected.</div>
                    <?php else: ?>
                        <?php foreach ($alerts as $alert): ?>
                            <?php 
                                $sevColor = 'text-blue-700 border-blue-200 bg-blue-50';
                                if ($alert['severity'] === 'critical') $sevColor = 'text-red-700 border-red-200 bg-red-50';
                                elseif ($alert['severity'] === 'high') $sevColor = 'text-red-650 border-red-150 bg-red-50';
                                elseif ($alert['severity'] === 'medium') $sevColor = 'text-amber-700 border-amber-200 bg-amber-50';
                            ?>
                            <div class="p-3 rounded-xl border flex flex-col gap-1 font-mono text-[10px] <?= $sevColor ?>">
                                <div class="flex justify-between items-center">
                                    <span class="font-extrabold uppercase tracking-widest">[<?= htmlspecialchars($alert['severity']) ?>]</span>
                                    <span class="opacity-75"><?= date('H:i m-d', strtotime($alert['created_at'])) ?></span>
                                </div>
                                <p class="text-[11px] font-sans leading-relaxed" style="color: var(--color-foreground-title);"><?= htmlspecialchars($alert['message']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activity Logs Panel -->
            <div class="cyber-card space-y-4" title="A list of recent actions on your account and their security risk scores.">
                <h3 class="text-xs font-bold uppercase tracking-wider flex items-center justify-between font-mono" style="color: var(--color-foreground-title);">
                    <span>Recent Events</span>
                    <i data-lucide="history" class="w-4 h-4" style="color: var(--color-primary);"></i>
                </h3>

                <div class="overflow-x-auto max-h-[300px] overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse" style="color: var(--color-foreground);">
                        <thead>
                            <tr class="border-b font-mono text-[10px]" style="border-color: var(--color-border); color: var(--color-primary);">
                                <th class="pb-2 font-medium">Time</th>
                                <th class="pb-2 font-medium">Action</th>
                                <th class="pb-2 font-medium text-center">Risk</th>
                                <th class="pb-2 font-medium">Threat Type</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="divide-color: var(--color-border);" id="logsTableBody">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="py-4 text-center" style="color: var(--color-foreground-muted);">No logs registered.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php 
                                        $riskColor = 'text-emerald-400';
                                        if ($log['risk_score'] >= 70) $riskColor = 'text-rose-500 font-bold';
                                        elseif ($log['risk_score'] >= 30) $riskColor = 'text-amber-500';
                                    ?>
                                    <tr class="hover:bg-slate-800/10 cursor-pointer transition-colors" onclick="loadLogAiDetails('<?= htmlspecialchars(json_encode($log)) ?>')">
                                        <td class="py-3 font-mono text-[10px]" style="color: var(--color-foreground-muted);"><?= date('H:i:s m-d', strtotime($log['created_at'])) ?></td>
                                        <td class="py-3 font-semibold" style="color: var(--color-foreground-title);"><?= htmlspecialchars($log['action']) ?></td>
                                        <td class="py-3 text-center <?= $riskColor ?> font-mono"><?= $log['risk_score'] ?>%</td>
                                        <td class="py-3" style="color: var(--color-foreground-title);"><?= htmlspecialchars($log['threat_classification']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tab Switching System
function switchTab(tab) {
    const btnEncrypt = document.getElementById('btn-tab-encrypt');
    const btnDecrypt = document.getElementById('btn-tab-decrypt');
    const panelEncrypt = document.getElementById('panel-encrypt');
    const panelDecrypt = document.getElementById('panel-decrypt');

    if (tab === 'encrypt') {
        btnEncrypt.className = "flex-1 py-2 rounded-lg text-xs font-mono font-bold uppercase transition-all flex items-center justify-center gap-2 bg-indigo-600 text-white border border-indigo-600";
        btnDecrypt.className = "flex-1 py-2 rounded-lg text-xs font-mono font-bold uppercase transition-all flex items-center justify-center gap-2 bg-cyan-50 text-cyan-700 border border-cyan-100 hover:bg-cyan-100";
        panelEncrypt.classList.remove('hidden');
        panelDecrypt.classList.add('hidden');
    } else {
        btnDecrypt.className = "flex-1 py-2 rounded-lg text-xs font-mono font-bold uppercase transition-all flex items-center justify-center gap-2 bg-cyan-500 text-white border border-cyan-500";
        btnEncrypt.className = "flex-1 py-2 rounded-lg text-xs font-mono font-bold uppercase transition-all flex items-center justify-center gap-2 bg-indigo-50 text-indigo-700 border border-indigo-100 hover:bg-indigo-100";
        panelDecrypt.classList.remove('hidden');
        panelEncrypt.classList.add('hidden');
    }
}

// Form submits AJAX encryption
document.getElementById('encryptForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-encrypt-submit');
    const resPanel = document.getElementById('encryptResult');
    
    btn.disabled = true;
    btn.innerHTML = `<span>Encrypting...</span>`;

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/encrypt', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        
        if (response.ok && data.status === 'success') {
            sessionStorage.setItem('last_encrypted_result', JSON.stringify({
                ciphertext: data.ciphertext,
                iv: data.iv,
                salt: data.salt,
                signature: data.signature,
                ai_profile: data.ai_profile,
                timestamp: Date.now()
            }));
            
            ToastManager.show("Message encrypted successfully.");
            window.location.reload();
        } else {
            ToastManager.show(data.message || 'Encryption failed', 'error');
            if (data.ai_profile) {
                renderAiReport(data.ai_profile);
            }
        }
    } catch (err) {
        ToastManager.show('Network execution failure.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="shield-check" class="w-4 h-4"></i><span>Transmit Encrypted Message</span>`;
        lucide.createIcons();
    }
});

// Decrypt form submit
document.getElementById('decryptForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-decrypt-submit');
    const resPanel = document.getElementById('decryptResult');
    const outField = document.getElementById('output-plaintext');

    btn.disabled = true;
    btn.innerHTML = `<span>Decrypting...</span>`;

    try {
        const formData = new FormData(this);
        const response = await fetch('<?= APP_URL ?>/decrypt', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();

        if (response.ok && data.status === 'success') {
            resPanel.classList.remove('hidden');
            outField.textContent = data.plaintext;
            ToastManager.show("Message decrypted successfully.");
        } else {
            ToastManager.show(data.message || 'Decryption failed.', 'error');
        }
    } catch(e) {
        ToastManager.show('Decryption network execution failure.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="unlock" class="w-4 h-4"></i><span>Decrypt Message</span>`;
        lucide.createIcons();
    }
});

// Copy Credentials
function copyCredentials() {
    const text = JSON.stringify({
        ciphertext: document.getElementById('output-ciphertext').value,
        iv: document.getElementById('output-iv').value,
        salt: document.getElementById('output-salt').value,
        signature: document.getElementById('output-signature').value
    });
    navigator.clipboard.writeText(text);
    ToastManager.show("Encryption details copied.");
}

// Render dynamic AI report
function renderAiReport(ai) {
    const box = document.getElementById('aiReportBox');
    const card = document.getElementById('aiWarningCard');
    
    if (ai.risk_score >= 70) {
        card.className = "cyber-card bg-rose-500/5 space-y-3 relative overflow-hidden border-red-500 animate-pulse";
    } else if (ai.risk_score >= 30) {
        card.className = "cyber-card bg-amber-500/5 space-y-3 relative overflow-hidden border-amber-500";
    } else {
        card.className = "cyber-card bg-cyan-500/5 space-y-3 relative overflow-hidden";
    }

    box.innerHTML = `
        <div class="flex items-center justify-between text-xs mb-1 font-mono">
            <span style="color: var(--color-foreground-muted);">Threat Rating:</span>
            <span class="font-bold ${ai.risk_score >= 70 ? 'text-rose-400' : (ai.risk_score >= 30 ? 'text-amber-400' : 'text-emerald-400')}">${ai.risk_score}%</span>
        </div>
        <div class="text-xs font-mono font-semibold ${ai.risk_score >= 30 ? 'text-rose-400' : 'text-cyan-400'}">
            Threat Type: ${ai.threat_classification}
        </div>
        <p class="text-[11px] leading-relaxed font-sans mt-2 border-t pt-2" style="border-color: var(--color-border); color: var(--color-foreground-muted);">
            <strong>Unusual details:</strong> ${ai.threat_details}
        </p>
        <p class="text-[11px] font-mono leading-relaxed mt-1" style="color: var(--color-primary);">
            <strong>What to do:</strong> ${ai.recommendations || 'System healthy. Continue.'}
        </p>
    `;
}

// Load log details to AI Insights panel on click
function loadLogAiDetails(logJson) {
    const log = JSON.parse(logJson);
    const box = document.getElementById('aiReportBox');
    const card = document.getElementById('aiWarningCard');
    
    if (log.risk_score >= 70) {
        card.className = "cyber-card bg-rose-500/5 space-y-3 relative overflow-hidden border-red-500";
    } else if (log.risk_score >= 30) {
        card.className = "cyber-card bg-amber-500/5 space-y-3 relative overflow-hidden border-amber-500";
    } else {
        card.className = "cyber-card bg-cyan-500/5 space-y-3 relative overflow-hidden";
    }

    box.innerHTML = `
        <div class="flex items-center justify-between text-xs mb-1 font-mono">
            <span style="color: var(--color-foreground-muted);">Action: <strong>${log.action}</strong></span>
            <span class="font-bold ${log.risk_score >= 70 ? 'text-rose-400' : (log.risk_score >= 30 ? 'text-amber-400' : 'text-emerald-400')}">${log.risk_score}% Risk</span>
        </div>
        <div class="text-xs font-mono font-semibold" style="color: var(--color-foreground-title);">
            Classification: ${log.threat_classification}
        </div>
        <p class="text-[11px] leading-relaxed font-sans mt-2 border-t pt-2" style="border-color: var(--color-border); color: var(--color-foreground-muted);">
            <strong>Log Details:</strong> ${log.threat_details || 'Verified normal activity'}
        </p>
        <p class="text-[11px] font-mono leading-relaxed mt-1" style="color: var(--color-primary);">
            <strong>Target IP Source:</strong> ${log.ip_address}
        </p>
        <span class="text-[8px] font-mono block mt-1" style="color: var(--color-foreground-muted);">${log.user_agent}</span>
    `;
}

function selectDecryptEnvelope(jsonStr) {
    if (!jsonStr) {
        const form = document.getElementById('decryptForm');
        form.querySelector('[name="ciphertext"]').value = '';
        form.querySelector('[name="iv"]').value = '';
        form.querySelector('[name="salt"]').value = '';
        form.querySelector('[name="signature"]').value = '';
        return;
    }
    populateDecryptEnvelope(jsonStr);
}

function populateDecryptEnvelope(jsonStr) {
    try {
        const payload = JSON.parse(jsonStr);
        const form = document.getElementById('decryptForm');
        form.querySelector('[name="ciphertext"]').value = payload.ciphertext;
        form.querySelector('[name="iv"]').value = payload.iv;
        form.querySelector('[name="salt"]').value = payload.salt;
        form.querySelector('[name="signature"]').value = payload.signature;
        ToastManager.show("Envelope parameters loaded.");
    } catch(e) {
        ToastManager.show("Failed to load parameters.", "error");
    }
}

// Chart.js 10-Incident Threat Telemetry Setup
document.addEventListener('DOMContentLoaded', () => {
    // Restore encryption details if redirected after encryption
    const lastResultStr = sessionStorage.getItem('last_encrypted_result');
    if (lastResultStr) {
        try {
            const lastResult = JSON.parse(lastResultStr);
            if (Date.now() - lastResult.timestamp < 10000) {
                const resPanel = document.getElementById('encryptResult');
                if (resPanel) {
                    resPanel.classList.remove('hidden');
                    document.getElementById('output-ciphertext').value = lastResult.ciphertext;
                    document.getElementById('output-iv').value = lastResult.iv;
                    document.getElementById('output-salt').value = lastResult.salt;
                    document.getElementById('output-signature').value = lastResult.signature;
                    if (lastResult.ai_profile) {
                        renderAiReport(lastResult.ai_profile);
                    }
                }
            }
        } catch(e) {}
        sessionStorage.removeItem('last_encrypted_result');
    }

    // Collect log data points dynamically from PHP
    const logData = <?= json_encode(array_reverse($logs)) ?>;
    const labels = logData.map((l, idx) => `Inc-${idx+1}`);
    const riskScores = logData.map(l => l.risk_score);

    const ctx = document.getElementById('threatHistoryChart').getContext('2d');
    
    // Gradient fill setup
    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, 'rgba(0, 255, 65, 0.2)');
    gradient.addColorStop(1, 'rgba(0, 255, 65, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length > 0 ? labels : ['No Data'],
            datasets: [{
                label: 'Heuristics Risk Rating (%)',
                data: riskScores.length > 0 ? riskScores : [0],
                borderColor: '#00FF41',
                backgroundColor: gradient,
                borderWidth: 2,
                tension: 0.35,
                fill: true,
                pointBackgroundColor: '#00FF41',
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#888888', font: { family: 'Fira Code' } },
                    min: 0,
                    max: 100
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#888888', font: { family: 'Fira Code' } }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});

function toggleRiskDetails() {
    const box = document.getElementById('riskDetailsBox');
    box.classList.toggle('hidden');
}
</script>
