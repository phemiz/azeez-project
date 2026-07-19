<?php
namespace App\Services;

use App\Core\Database;

/**
 * Enterprise Security Alerts Engine
 * Analyzes logs, login attempts, and session states to automatically fire alerts
 * (Failed logins, Expired OTP, Password changes, New devices, Suspicious IPs,
 * Brute force attempts, High risk scores, Multiple concurrent sessions).
 */
class AlertService {
    private Database $db;
    private LoggingService $logger;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new LoggingService();
    }

    /**
     * Dispatch/trigger a new security alert.
     */
    public function triggerAlert(string $severity, string $message, ?int $userId = null): void {
        try {
            // 1. Insert alert in database
            $sql = "INSERT INTO security_alerts (user_id, severity, message, status) VALUES (?, ?, ?, 'open')";
            $this->db->query($sql, [$userId, $severity, $message]);

            // 2. Centralized dual-logging entry
            $this->logger->log('security_alert', $userId, 50.0, 'Security Alert Triggered', $severity, $message);

            // 3. Dispatch Email Notification (Mock dispatch stub)
            $this->dispatchEmailNotification($severity, $message, $userId);
        } catch (\Exception $e) {
            error_log("Alert triggering failed: " . $e->getMessage());
        }
    }

    /**
     * Audits for Brute Force Login attempts (Failed logins check).
     */
    public function checkFailedLogins(string $username, string $ip): void {
        try {
            // Count failed attempts in last 15 minutes
            $count = (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM login_attempts 
                 WHERE username = ? AND ip_address = ? AND status = 'failed' 
                 AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
                [$username, $ip]
            );

            if ($count >= 5) {
                $this->triggerAlert('critical', "Potential Brute Force: 5+ failed login attempts within 15 minutes for operator username: '{$username}' from source IP: {$ip}.");
            }
        } catch (\Exception $e) {
            error_log("Check failed logins anomaly failed: " . $e->getMessage());
        }
    }

    /**
     * Audits for new device logins.
     */
    public function checkNewDevice(int $userId, string $userAgent): void {
        try {
            // Count times user agent has been used by this user in last 3 months
            $count = (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM activity_logs 
                 WHERE user_id = ? AND user_agent = ? AND created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)",
                [$userId, $userAgent]
            );

            if ($count === 0) {
                // First time this device has been observed
                $this->triggerAlert('medium', "New device verification: Portal login observed from previously unrecorded browser signature: " . substr($userAgent, 0, 100) . "...", $userId);
            }
        } catch (\Exception $e) {
            error_log("Check new device failed: " . $e->getMessage());
        }
    }

    /**
     * Audits for logins from suspicious/unknown IPs.
     */
    public function checkSuspiciousIP(int $userId, string $ip): void {
        try {
            // Check if this IP has been logged by this user before
            $count = (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM activity_logs 
                 WHERE user_id = ? AND ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)",
                [$userId, $ip]
            );

            if ($count === 0) {
                $this->triggerAlert('medium', "Security alert: User session initialized from unknown IP address location: {$ip}.", $userId);
            }
        } catch (\Exception $e) {
            error_log("Check suspicious IP failed: " . $e->getMessage());
        }
    }

    /**
     * Audits for multiple concurrent active user sessions.
     */
    public function checkMultipleSessions(int $userId): void {
        try {
            // Count active sessions (last activity within 30 minutes)
            $count = (int)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM sessions 
                 WHERE user_id = ? AND last_activity > (UNIX_TIMESTAMP() - 1800)",
                [$userId]
            );

            if ($count > 1) {
                $this->triggerAlert('high', "Multi-session alert: {$count} concurrent active sessions detected for user node.", $userId);
            }
        } catch (\Exception $e) {
            error_log("Check concurrent sessions failed: " . $e->getMessage());
        }
    }

    /**
     * Mock notification dispatcher layout (Ready for SMTP integration).
     */
    private function dispatchEmailNotification(string $severity, string $message, ?int $userId = null): void {
        // Retrieve user secure contact
        if (!$userId) return;
        
        try {
            $user = $this->db->fetch("SELECT username, email FROM users WHERE id = ?", [$userId]);
            if ($user && $user['email']) {
                $subject = "[GSM-GUARD ALERT] - " . strtoupper($severity) . " Severity Event Flagged";
                $headers = "From: security@gsmsecurity.net\r\n" .
                           "Reply-To: security@gsmsecurity.net\r\n" .
                           "Content-Type: text/html; charset=UTF-8\r\n";
                
                $body = "
                <html>
                <body style='font-family: monospace; background-color: #030712; color: #f3f4f6; padding: 20px;'>
                    <div style='border: 1px solid #ef4444; border-radius: 12px; padding: 20px; max-width: 600px; margin: 0 auto; background-color: #090d16;'>
                        <h2 style='color: #ef4444; margin-top:0;'>SECURE TRANSCEIVER ALERT</h2>
                        <hr style='border-color: #1f2937;' />
                        <p><strong>Target Operator Node:</strong> {$user['username']}</p>
                        <p><strong>Threat Severity:</strong> <span style='color: #ef4444; font-weight:bold;'>".strtoupper($severity)."</span></p>
                        <p><strong>Audit log event details:</strong><br/>
                        <code style='background-color:#1e293b; padding:4px 8px; border-radius:4px; display:block; margin-top:8px;'>{$message}</code></p>
                        <hr style='border-color: #1f2937;' />
                        <span style='font-size:10px; color:#6b7280;'>Timestamp: " . date('Y-m-d H:i:s') . " &middot; GSM-GUARD automatic intrusion detection engine.</span>
                    </div>
                </body>
                </html>";

                // Under local environments, mail() might not be configured, so we output the stub to syslogs
                error_log("SMTP Alert Dispatch to {$user['email']}: [Subject: {$subject}] [Message: {$message}]");
            }
        } catch (\Exception $e) {
            error_log("Failed to compose SMTP notification details: " . $e->getMessage());
        }
    }
}
