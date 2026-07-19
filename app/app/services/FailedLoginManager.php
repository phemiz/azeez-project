<?php
namespace App\Services;

use App\Core\Database;

/**
 * Failed Login Auditing Service
 * Tracks failed login attempts per username and IP, determining rate-limit triggers.
 */
class FailedLoginManager {
    private Database $db;
    private int $maxFails = 5;
    private int $timeWindow = 900; // 15 minutes (900 seconds)

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Record a login attempt state.
     */
    public function recordAttempt(string $username, string $ip, string $status): void {
        $sql = "INSERT INTO login_attempts (username, ip_address, status) VALUES (?, ?, ?)";
        $this->db->query($sql, [$username, $ip, $status]);
    }

    /**
     * Checks if a user has exceeded maximum failures.
     */
    public function hasExceededLimit(string $username, string $ip): bool {
        $sinceTime = date('Y-m-d H:i:s', time() - $this->timeWindow);

        // Count recent failed attempts from this IP
        $ipFailCount = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM login_attempts 
             WHERE ip_address = ? AND status = 'failed' AND attempt_time > ?",
            [$ip, $sinceTime]
        );

        if ($ipFailCount >= $this->maxFails) {
            return true;
        }

        // Count recent failed attempts for this username
        $userFailCount = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM login_attempts 
             WHERE username = ? AND status = 'failed' AND attempt_time > ?",
            [$username, $sinceTime]
        );

        if ($userFailCount >= $this->maxFails) {
            return true;
        }

        return false;
    }

    /**
     * Reset failed attempts tracking (done upon successful login).
     */
    public function resetAttempts(string $username, string $ip): void {
        // We do not delete rows to keep log trail history, but update status
        $this->db->query(
            "UPDATE login_attempts SET status = 'success' 
             WHERE (username = ? OR ip_address = ?) AND status = 'failed'",
            [$username, $ip]
        );
    }
}
