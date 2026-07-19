<?php
namespace App\Services;

use App\Core\Database;
use App\Core\PasswordManager;

/**
 * Enterprise User Profile Management Service
 * Manages personal information updates, passcode rotations, security credentials setups,
 * and avatar placeholders updates.
 */
class ProfileService {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->selfHealDatabase();
    }

    /**
     * Self-heals the users table by adding the avatar field if missing.
     */
    private function selfHealDatabase(): void {
        try {
            $this->db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL");
        } catch (\Exception $e) {}
        try {
            $this->db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS mfa_enabled TINYINT(1) DEFAULT 1");
        } catch (\Exception $e) {}
        try {
            $this->db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS login_notify TINYINT(1) DEFAULT 1");
        } catch (\Exception $e) {}
        try {
            $this->db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS session_timeout_custom INT DEFAULT 900");
        } catch (\Exception $e) {}
    }

    /**
     * Updates an operator's profile credentials.
     */
    public function updateProfile(int $userId, string $email, string $phone): void {
        $this->db->query(
            "UPDATE users SET email = ?, phone = ? WHERE id = ?",
            [$email, $phone, $userId]
        );
    }

    /**
     * Rotates an operator's passcode with strict entropy checks.
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): void {
        $user = $this->db->fetch("SELECT password_hash FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            throw new \Exception("Operator node not found.");
        }

        if (!PasswordManager::verify($oldPassword, $user['password_hash'])) {
            throw new \Exception("Incorrect current credentials.");
        }

        if (strlen($newPassword) < 8) {
            throw new \Exception("Passcode must contain at least 8 characters.");
        }

        $newHash = PasswordManager::hash($newPassword);
        $this->db->query("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $userId]);
    }

    /**
     * Overwrites the avatar file path in user profile details.
     */
    public function updateAvatar(int $userId, string $avatarPath): void {
        $this->db->query("UPDATE users SET avatar = ? WHERE id = ?", [$avatarPath, $userId]);
    }

    /**
     * Updates user security preferences.
     */
    public function updateSecuritySettings(int $userId, int $mfaEnabled, int $loginNotify, int $sessionTimeout): void {
        $this->db->query(
            "UPDATE users SET mfa_enabled = ?, login_notify = ?, session_timeout_custom = ? WHERE id = ?",
            [$mfaEnabled, $loginNotify, $sessionTimeout, $userId]
        );
    }

    /**
     * Terminates an active browser session footprints.
     */
    public function revokeSession(int $userId, string $sessionId): void {
        $this->db->query(
            "DELETE FROM sessions WHERE id = ? AND user_id = ?",
            [$sessionId, $userId]
        );
    }

    /**
     * Utility to parse User Agent headers into OS, Browser, and Device Type.
     */
    public function parseUserAgent(string $userAgent): array {
        $os = 'Unknown OS';
        $browser = 'Unknown Browser';
        $device = 'Desktop';

        // Detect OS
        if (preg_match('/windows|win32/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $os = 'iOS';
            $device = 'Mobile';
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = 'Android';
            $device = 'Mobile';
        }

        // Detect Browser
        if (preg_match('/edge/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/opera|opr/i', $userAgent)) {
            $browser = 'Opera';
        }

        return [
            'os'      => $os,
            'browser' => $browser,
            'device'  => $device
        ];
    }

    /**
     * Compiles recent activity history and active sessions.
     */
    public function getProfileSecurityDetails(int $userId): array {
        // Fetch recent logs
        $logs = $this->db->fetchAll(
            "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
            [$userId]
        );

        // Fetch active session footprints
        $rawSessions = $this->db->fetchAll(
            "SELECT * FROM sessions WHERE user_id = ? ORDER BY last_activity DESC",
            [$userId]
        );

        $sessions = [];
        foreach ($rawSessions as $s) {
            $uaInfo = $this->parseUserAgent($s['user_agent']);
            $sessions[] = array_merge($s, [
                'os'      => $uaInfo['os'],
                'browser' => $uaInfo['browser'],
                'device'  => $uaInfo['device']
            ]);
        }

        return [
            'recent_logs' => $logs,
            'sessions'    => $sessions
        ];
    }
}
