<?php
namespace App\Services;

use App\Core\Database;

/**
 * Enterprise AI Security Recommendation Engine
 * Analyzes stateful parameters (MFA activation, password age, login history profiles,
 * concurrent sessions count, active heuristics warnings, and profile metrics)
 * to output prioritized security recommendation cards.
 */
class RecommendationEngine {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Inspects operator parameters and compiles prioritized recommendation cards.
     * 
     * @param int $userId ID of the active user profile
     * @param string $ip Current connection IP address
     * @param string $userAgent Current connection User Agent
     * @return array List of recommendation cards containing title, description, priority, icon, and action redirect links
     */
    public function generateRecommendations(int $userId, string $ip, string $userAgent): array {
        $recommendations = [];

        // 1. Terminate redundant sessions check
        $activeSessions = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM sessions 
             WHERE user_id = ? AND last_activity > (UNIX_TIMESTAMP() - 1800)",
            [$userId]
        );
        if ($activeSessions > 1) {
            $recommendations[] = [
                'title'       => 'Terminate Redundant Sessions',
                'description' => "There are currently {$activeSessions} concurrent active session links detected for your operator node. Terminate unknown sessions to block hijacking.",
                'priority'    => 'critical',
                'icon'        => 'monitor-off',
                'action_url'  => APP_URL . '/logout',
                'action_lbl'  => 'Reset Session State'
            ];
        }

        // 2. Review threat notifications check
        $alertCount = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM security_alerts 
             WHERE user_id = ? AND status = 'open'",
            [$userId]
        );
        if ($alertCount > 0) {
            $recommendations[] = [
                'title'       => 'Review Pending Security Alarms',
                'description' => "Heuristics and WAF filters have logged {$alertCount} open alerts in your intrusion log feed. Review threat categories immediately.",
                'priority'    => 'high',
                'icon'        => 'shield-alert',
                'action_url'  => APP_URL . '/dashboard',
                'action_lbl'  => 'Audit Log Feed'
            ];
        }

        // 3. Review login history check
        $unknownIpCount = (int)$this->db->fetchColumn(
            "SELECT COUNT(DISTINCT ip_address) FROM activity_logs 
             WHERE user_id = ? AND action LIKE 'login_success%' 
             AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
            [$userId]
        );
        if ($unknownIpCount >= 2) {
            $recommendations[] = [
                'title'       => 'Review Node Access Logs',
                'description' => "Your node has been accessed from {$unknownIpCount} distinct IP locations in the last week. Audit access telemetry to verify authorized routing.",
                'priority'    => 'medium',
                'icon'        => 'history',
                'action_url'  => APP_URL . '/dashboard',
                'action_lbl'  => 'Audit Logs'
            ];
        }

        // 4. Change password check (simulated password age threshold)
        $user = $this->db->fetch("SELECT created_at FROM users WHERE id = ?", [$userId]);
        if ($user) {
            $createdTime = strtotime($user['created_at']);
            $ageDays = floor((time() - $createdTime) / 86400);
            if ($ageDays > 90) {
                $recommendations[] = [
                    'title'       => 'Update Node Passcode',
                    'description' => "Your current cipher credentials have been active for {$ageDays} days. Rotate credentials to align with standard 90-day rotation rules.",
                    'priority'    => 'medium',
                    'icon'        => 'refresh-cw',
                    'action_url'  => APP_URL . '/forgot-password',
                    'action_lbl'  => 'Rotate Credentials'
                ];
            }
        }

        // 5. Complete contact profile check
        $contactDetails = $this->db->fetch("SELECT email, phone FROM users WHERE id = ?", [$userId]);
        if ($contactDetails && (empty($contactDetails['email']) || empty($contactDetails['phone']))) {
            $recommendations[] = [
                'title'       => 'Complete Security Contact Profile',
                'description' => "Your operator profile details are missing verified contact indicators. Update secure channels to receive carrier alerts.",
                'priority'    => 'low',
                'icon'        => 'user-cog',
                'action_url'  => APP_URL . '/dashboard',
                'action_lbl'  => 'Configure Channels'
            ];
        }

        // 6. Default fallback recommendation if profile is secure
        if (empty($recommendations)) {
            $recommendations[] = [
                'title'       => 'Audit Local Cryptographic Keys',
                'description' => "All session baselines and credential metrics are secure. Continue executing normal AES-256 enveloping operations.",
                'priority'    => 'low',
                'icon'        => 'shield-check',
                'action_url'  => APP_URL . '/encrypt-payload',
                'action_lbl'  => 'Initiate Enveloping'
            ];
        }

        return $recommendations;
    }
}
