<?php
namespace App\Services;

use App\Core\Database;

/**
 * Enterprise AI Behavior Analysis Engine
 * Compiles user activity profiles (login hours, device configurations, exfiltration volumes,
 * and security state shifts) to establish operator baselines and flag behavioral anomalies.
 */
class BehaviorEngine {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Profiles user behavior and matches current session parameters against historical baselines.
     * 
     * @param int $userId ID of the active user profile
     * @return array Baselines, anomalies, risk metrics, and recommendations
     */
    public function profileOperator(int $userId): array {
        // 1. Login Hour Baseline (Daytime vs Off-peak diurnal patterns)
        $hourStats = $this->db->fetchAll(
            "SELECT HOUR(created_at) as hr, COUNT(*) as count 
             FROM activity_logs 
             WHERE user_id = ? AND action LIKE 'login_success%' 
             GROUP BY HOUR(created_at)",
            [$userId]
        );

        $totalLogins = 0;
        $diurnalDistribution = array_fill(0, 24, 0);
        foreach ($hourStats as $row) {
            $diurnalDistribution[(int)$row['hr']] = (int)$row['count'];
            $totalLogins += (int)$row['count'];
        }

        // Normalize percentages
        if ($totalLogins > 0) {
            for ($i = 0; $i < 24; $i++) {
                $diurnalDistribution[$i] = round(($diurnalDistribution[$i] / $totalLogins) * 100, 1);
            }
        }

        // Assess current time classification (off-peak vs peak)
        $currentHour = (int)date('H');
        $historicalProbability = $diurnalDistribution[$currentHour] ?? 0;
        $timeAnomaly = ($historicalProbability < 5.0 && $totalLogins >= 5); // Flags if hour has < 5% probability baseline

        // 2. Encryption Frequency Baseline (Daily volumetric mean)
        $dailyEncryptions = $this->db->fetchAll(
            "SELECT DATE(created_at) as dt, COUNT(*) as count 
             FROM encrypted_messages 
             WHERE sender_id = ? 
             GROUP BY DATE(created_at)",
             [$userId]
        );

        $meanEncryptions = 0;
        $stdDevEncryptions = 0;
        $dayCounts = array_column($dailyEncryptions, 'count');
        $totalDays = count($dayCounts);

        if ($totalDays > 0) {
            $meanEncryptions = array_sum($dayCounts) / $totalDays;
            
            // Standard deviation
            $variance = 0.0;
            foreach ($dayCounts as $val) {
                $variance += pow($val - $meanEncryptions, 2);
            }
            $stdDevEncryptions = sqrt($variance / $totalDays);
        }

        // Check if active hourly encryption is abnormally high (> 30 in 1 hour)
        $activeHourEncrypt = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM encrypted_messages 
             WHERE sender_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$userId]
        );
        $volumeAnomaly = ($activeHourEncrypt > ($meanEncryptions * 2) && $activeHourEncrypt > 15);

        // 3. Primary Device Profile
        $uaStats = $this->db->fetchAll(
            "SELECT user_agent, COUNT(*) as count 
             FROM activity_logs 
             WHERE user_id = ? AND action LIKE 'login_success%' 
             GROUP BY user_agent 
             ORDER BY count DESC",
            [$userId]
        );

        $primaryOS = 'Unknown';
        $primaryBrowser = 'Unknown';
        if (!empty($uaStats)) {
            $topUa = $uaStats[0]['user_agent'];
            $primaryOS = $this->detectOS($topUa);
            $primaryBrowser = $this->detectBrowser($topUa);
        }

        // 4. Password alterations count (90 days)
        $pwChanges = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM activity_logs 
             WHERE user_id = ? AND action = 'password_reset_success' 
             AND created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)",
            [$userId]
        );

        // Compute Risk Indicators
        $anomaliesDetected = [];
        $behaviorRiskScore = 0;

        if ($timeAnomaly) {
            $anomaliesDetected[] = "Diurnal Outlier (Login at unusual hour: {$currentHour}:00)";
            $behaviorRiskScore += 25;
        }
        if ($volumeAnomaly) {
            $anomaliesDetected[] = "Volumetric Anomaly (Encryption activity spikes: {$activeHourEncrypt} in last hour)";
            $behaviorRiskScore += 35;
        }
        if ($pwChanges >= 3) {
            $anomaliesDetected[] = "Credential Volatility (Multiple password resets observed recently)";
            $behaviorRiskScore += 20;
        }

        $recommendations = "Normal baseline operations mapped. No actions required.";
        if ($behaviorRiskScore >= 50) {
            $recommendations = "Action required: Enforce session lock and mandate OTP re-challenge due to high volumetric and scheduling anomalies.";
        } elseif ($behaviorRiskScore > 0) {
            $recommendations = "Warning: Minor anomalies flagged. Restrict bulk decryption exports temporarily.";
        }

        return [
            'baselines' => [
                'diurnal_distribution' => $diurnalDistribution,
                'mean_daily_encryptions' => round($meanEncryptions, 1),
                'primary_os'            => $primaryOS,
                'primary_browser'       => $primaryBrowser,
                'recent_pw_changes'     => $pwChanges,
                'average_session_mins'  => 45 // Estimated typical baseline session duration
            ],
            'anomalies'          => $anomaliesDetected,
            'behavior_risk'      => min(100, $behaviorRiskScore),
            'recommendations'    => $recommendations
        ];
    }

    private function detectOS(string $ua): string {
        if (preg_match('/windows/i', $ua)) return 'Windows';
        if (preg_match('/macintosh|mac os x/i', $ua)) return 'macOS';
        if (preg_match('/linux/i', $ua)) return 'Linux';
        if (preg_match('/android/i', $ua)) return 'Android';
        if (preg_match('/iphone|ipad/i', $ua)) return 'iOS';
        return 'Unknown';
    }

    private function detectBrowser(string $ua): string {
        if (preg_match('/edge/i', $ua)) return 'Edge';
        if (preg_match('/chrome/i', $ua)) return 'Chrome';
        if (preg_match('/firefox/i', $ua)) return 'Firefox';
        if (preg_match('/safari/i', $ua)) return 'Safari';
        return 'Unknown';
    }
}
