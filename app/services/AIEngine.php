<?php
namespace App\Services;

use App\Core\Database;

/**
 * Enterprise AI Threat Detection Heuristic Engine
 * Performs behavioral profiling, impossible travel detection, content signature scanning,
 * and dynamic risk-score calculations for security events.
 */
class AIEngine {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Analyzes a security event and profiles the threat status.
     * 
     * @param int|null $userId ID of the user (if logged in)
     * @param string $action Action being performed (e.g., 'login_failed', 'decrypt_message')
     * @param string $ipAddress Incoming IP address
     * @param string $userAgent Incoming User Agent
     * @param array $payload Optional parameter data to analyze (e.g. GSM payloads, metadata)
     * @return array Security profile containing risk score, threat class, severity, and recommendations
     */
    public function analyzeSecurityEvent(?int $userId, string $action, string $ipAddress, string $userAgent, array $payload = []): array {
        $riskScore = 0;
        $classifications = [];
        $severity = 'low';
        $details = [];

        // 1. Check for Brute Force Login Indicators
        if ($action === 'login_failed') {
            $fiveMinsAgo = date('Y-m-d H:i:s', time() - 300);
            $failedCount = (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM activity_logs WHERE ip_address = ? AND action = 'login_failed' AND created_at > ?",
                [$ipAddress, $fiveMinsAgo]
            );

            if ($failedCount >= 5) {
                $riskScore += 80;
                $classifications[] = 'Brute Force Attack';
                $severity = 'critical';
                $details[] = "Detected {$failedCount} failed login attempts in last 5 minutes from IP: {$ipAddress}.";
            } elseif ($failedCount >= 3) {
                $riskScore += 45;
                $classifications[] = 'Suspicious Login Activity';
                $severity = 'medium';
                $details[] = "Detected {$failedCount} failed login attempts in last 5 minutes.";
            }
        }

        // 2. Behavioral Profiling (Impossible Travel Velocity / Location Mismatch)
        if ($userId !== null) {
            $lastActivity = $this->db->fetch(
                "SELECT ip_address, user_agent, created_at FROM activity_logs 
                 WHERE user_id = ? AND action = 'login_success' 
                 ORDER BY created_at DESC LIMIT 1",
                [$userId]
            );

            if ($lastActivity) {
                // If IP changed, trigger geographical velocity review (impossible travel)
                if ($lastActivity['ip_address'] !== $ipAddress) {
                    $timeDifference = time() - strtotime($lastActivity['created_at']);
                    
                    if ($timeDifference < 900) { // Under 15 minutes
                        $riskScore += AI_WEIGHT_VELOCITY_VIOLATION;
                        $classifications[] = 'Impossible Travel Velocity';
                        $severity = 'high';
                        $details[] = "IP address changed from {$lastActivity['ip_address']} to {$ipAddress} within " . round($timeDifference / 60) . " minutes.";
                    } else {
                        $riskScore += 15; // Low warning for simple IP shift
                    }
                }

                // If User-Agent changed mid-session, flag hijacking risks
                if ($lastActivity['user_agent'] !== $userAgent) {
                    $riskScore += AI_WEIGHT_USER_AGENT_CHANGE;
                    $classifications[] = 'Session Hijack Vulnerability';
                    $severity = 'medium';
                    $details[] = "User Agent changed within session window.";
                }
            }
        }

        // 3. Payload Static Heuristic Scanner (Detecting SQLi/XSS Attempts in Logs/SMS)
        foreach ($payload as $key => $val) {
            if (is_string($val)) {
                // SQL Injection patterns
                if (preg_match('/(union\s+select|select\s+.*\s+from|insert\s+into|delete\s+from|drop\s+table|alter\s+table|or\s+\d+\s*=\s*\d+|[\x27\x22]\s*or\s*[\x27\x22]|--|#)/i', $val)) {
                    $riskScore += 65;
                    $classifications[] = 'SQL Injection Attempt';
                    $severity = 'critical';
                    $details[] = "SQL injection syntax matched in field: '{$key}'.";
                }
                // XSS patterns
                if (preg_match('/(<script|javascript:|onload|onerror|alert\()/i', $val)) {
                    $riskScore += 50;
                    $classifications[] = 'Cross-Site Scripting (XSS) Attempt';
                    $severity = 'high';
                    $details[] = "XSS script payload matched in field: '{$key}'.";
                }
            }
        }

        // 4. GSM-Specific Heuristic Rules (Base Station / SMS Spoofing checks)
        if (isset($payload['gsm_metadata'])) {
            $meta = $payload['gsm_metadata'];
            // Rule A: Empty Routing Center/SMSC representation often means Base Station Spoofing (IMSI Catcher)
            if (empty($meta['smsc']) || $meta['smsc'] === '0000000000') {
                $riskScore += 60;
                $classifications[] = 'Base Station Spoofing (IMSI Catcher)';
                $severity = 'high';
                $details[] = "GSM packet lacks a valid Service Center Address (SMSC). Possible IMSI Catcher simulation.";
            }
            // Rule B: Binary SMS targeting silent ping ports (SMS Type 0)
            if (isset($meta['protocol_id']) && $meta['protocol_id'] == '64') { // Type 0 silent SMS
                $riskScore += 45;
                $classifications[] = 'Silent SMS Tracker (Type 0)';
                $severity = 'medium';
                $details[] = "Type 0 Silent SMS indicator identified (tracking device trace).";
            }
        }

        // Cap Risk Score between 0 and 100
        $riskScore = min($riskScore, 100);

        // Deduce dominant threat classification
        $threatClass = 'Normal';
        if (!empty($classifications)) {
            $threatClass = implode(', ', array_unique($classifications));
        }

        // Compile recommendations
        $recommendations = $this->generateRecommendations($riskScore, $classifications);

        return [
            'risk_score'            => $riskScore,
            'threat_classification' => $threatClass,
            'severity'              => $severity,
            'threat_details'        => implode('; ', $details) ?: 'No anomalies detected.',
            'recommendations'       => $recommendations
        ];
    }

    /**
     * Resolves actionable security recommendations based on event metrics.
     */
    private function generateRecommendations(int $riskScore, array $classifications): string {
        if ($riskScore === 0) {
            return "No action required. Connection secure.";
        }

        $actions = [];

        if (in_array('Brute Force Attack', $classifications)) {
            $actions[] = "Lock connection source IP temporarily.";
            $actions[] = "Enforce rate limiting and trigger strict CAPTCHA on subsequent attempts.";
        }
        if (in_array('Impossible Travel Velocity', $classifications) || in_array('Session Hijack Vulnerability', $classifications)) {
            $actions[] = "Invalidate existing active session cookies.";
            $actions[] = "Force 2FA/OTP validation challenge on current request.";
        }
        if (in_array('SQL Injection Attempt', $classifications) || in_array('Cross-Site Scripting (XSS) Attempt', $classifications)) {
            $actions[] = "Blacklist IP source from stateful requests.";
            $actions[] = "Ensure WAF (Web Application Firewall) signatures are updated.";
        }
        if (in_array('Base Station Spoofing (IMSI Catcher)', $classifications)) {
            $actions[] = "GSM message decryption rejected: Encryption key-exchange is vulnerable on untrusted cell towers.";
            $actions[] = "Verify local GSM signal base band protocol version (force 4G/5G, restrict 2G fallbacks).";
        }
        if (in_array('Silent SMS Tracker (Type 0)', $classifications)) {
            $actions[] = "Notify recipient device of silent location triangulation attempts.";
        }

        if (empty($actions)) {
            $actions[] = "Increase audit logging severity for connection context.";
        }

        return implode(' ', $actions);
    }
}
