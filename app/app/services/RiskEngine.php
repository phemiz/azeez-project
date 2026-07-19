<?php
namespace App\Services;

use App\Core\Database;

/**
 * Enterprise AI Risk Scoring Engine
 * Analyzes stateful parameters (Failed logins, Unknown devices, Unknown browsers,
 * Unknown IPs, Rapid login rates, Geo-location jumps, Encryption speeds, and Session anomalies)
 * to output a calculated Risk Score index (0-100) and actionable security recommendations.
 */
class RiskEngine {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Calculates the cumulative risk score for a transaction context.
     * 
     * @param int $userId ID of the active user profile
     * @param string $ip Current connection IP address
     * @param string $userAgent Current connection User Agent
     * @return array Calculated score index, scoring breakdown, and recommendations
     */
    public function evaluateRisk(int $userId, string $ip, string $userAgent): array {
        $score = 0;
        $breakdown = [];

        // 1. Check recent failed logins (Weight: +15 per failure in last 30 minutes, max 45)
        $failedLoginsCount = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM login_attempts 
             WHERE username = (SELECT username FROM users WHERE id = ?) 
             AND status = 'failed' AND attempt_time > DATE_SUB(NOW(), INTERVAL 30 MINUTE)",
            [$userId]
        );
        $failedLoginsPenalty = min(45, $failedLoginsCount * 15);
        $score += $failedLoginsPenalty;
        $breakdown['failed_logins'] = $failedLoginsPenalty;

        // 2. Unknown Device & Browser check
        $uaHistory = $this->db->fetchAll(
            "SELECT DISTINCT user_agent FROM activity_logs 
             WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)",
            [$userId]
        );

        $knownDevice = false;
        $knownBrowser = false;
        $incomingDevice = $this->detectDevice($userAgent);
        $incomingBrowser = $this->detectBrowser($userAgent);

        foreach ($uaHistory as $hist) {
            $histDevice = $this->detectDevice($hist['user_agent']);
            $histBrowser = $this->detectBrowser($hist['user_agent']);
            if ($histDevice === $incomingDevice) $knownDevice = true;
            if ($histBrowser === $incomingBrowser) $knownBrowser = true;
        }

        $devicePenalty = !$knownDevice ? 20 : 0;
        $browserPenalty = !$knownBrowser ? 15 : 0;
        $score += $devicePenalty + $browserPenalty;
        $breakdown['unknown_device'] = $devicePenalty;
        $breakdown['unknown_browser'] = $browserPenalty;

        // 3. Unknown IP Check (Weight: +25 if IP has not been logged in last 90 days)
        $ipRecorded = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM activity_logs 
             WHERE user_id = ? AND ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)",
            [$userId, $ip]
        );
        $ipPenalty = ($ipRecorded === 0) ? 25 : 0;
        $score += $ipPenalty;
        $breakdown['unknown_ip'] = $ipPenalty;

        // 4. Rapid Logins Check (Weight: +30 if user logged in >= 3 times in last 10 minutes)
        $loginHits = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM activity_logs 
             WHERE user_id = ? AND action LIKE 'login_success%' 
             AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)",
            [$userId]
        );
        $rapidLoginsPenalty = ($loginHits >= 3) ? 30 : 0;
        $score += $rapidLoginsPenalty;
        $breakdown['rapid_logins'] = $rapidLoginsPenalty;

        // 5. Geographical Location Jump (Impossible Travel Velocity)
        $lastLog = $this->db->fetch(
            "SELECT ip_address, created_at FROM activity_logs 
             WHERE user_id = ? AND action LIKE 'login_success%' 
             ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );

        $travelPenalty = 0;
        if ($lastLog && $lastLog['ip_address'] !== $ip) {
            $timeGap = time() - strtotime($lastLog['created_at']);
            if ($timeGap < 1800) { // Shifts under 30 minutes
                $travelPenalty = 45;
            } elseif ($timeGap < 3600) { // Shifts under 1 hour
                $travelPenalty = 25;
            }
        }
        $score += $travelPenalty;
        $breakdown['impossible_travel'] = $travelPenalty;

        // 6. Data Exfiltration Check (Abnormally high encryption frequency: > 30 in 1 hour)
        $encryptCount = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM encrypted_messages 
             WHERE sender_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$userId]
        );
        $exfiltrationPenalty = ($encryptCount > 30) ? 30 : 0;
        $score += $exfiltrationPenalty;
        $breakdown['encryption_frequency'] = $exfiltrationPenalty;

        // 7. Session Anomalies (UA changes mid-session or multiple active tokens)
        $activeSessions = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM sessions 
             WHERE user_id = ? AND last_activity > (UNIX_TIMESTAMP() - 1800)",
            [$userId]
        );
        $sessionAnomalyPenalty = ($activeSessions > 1) ? 35 : 0;
        $score += $sessionAnomalyPenalty;
        $breakdown['session_anomaly'] = $sessionAnomalyPenalty;

        // Bound final score between 0 and 100
        $finalScore = min(100, $score);

        // Compile recommendations
        $recommendations = $this->compileRecommendations($finalScore, $breakdown);

        return [
            'risk_score'      => $finalScore,
            'breakdown'       => $breakdown,
            'recommendations' => $recommendations
        ];
    }

    /**
     * Determines device type.
     */
    private function detectDevice(string $ua): string {
        if (preg_match('/mobile|phone|ipod/i', $ua)) return 'Mobile';
        if (preg_match('/tablet|ipad/i', $ua)) return 'Tablet';
        return 'Desktop';
    }

    /**
     * Determines browser name.
     */
    private function detectBrowser(string $ua): string {
        if (preg_match('/edge/i', $ua)) return 'Edge';
        if (preg_match('/chrome/i', $ua)) return 'Chrome';
        if (preg_match('/firefox/i', $ua)) return 'Firefox';
        if (preg_match('/safari/i', $ua)) return 'Safari';
        return 'Unknown';
    }

    /**
     * Resolves actionable mitigations.
     */
    private function compileRecommendations(int $score, array $breakdown): string {
        if ($score < 30) {
            return "Node signature matches normal behavior. Access allowed.";
        }

        $actions = [];
        if ($breakdown['impossible_travel'] > 0 || $breakdown['session_anomaly'] > 0) {
            $actions[] = "Invalidate active session tokens and force MFA/OTP verification.";
        }
        if ($breakdown['failed_logins'] >= 30) {
            $actions[] = "Impose a sliding 30-minute IP address block against credentials inputs.";
        }
        if ($breakdown['encryption_frequency'] > 0) {
            $actions[] = "Limit message encryption throughput to prevent bulk data exfiltration.";
        }
        if ($breakdown['unknown_ip'] > 0 || $breakdown['unknown_device'] > 0) {
            $actions[] = "Transmit email security alert to operator secure inbox.";
        }

        return implode(' ', $actions);
    }
}
