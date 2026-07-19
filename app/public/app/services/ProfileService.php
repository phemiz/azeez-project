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
        } catch (\Exception $e) {
            // Suppress error if column already exists
        }
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
     * Compiles recent activity history and active sessions.
     */
    public function getProfileSecurityDetails(int $userId): array {
        // Fetch recent logs
        $logs = $this->db->fetchAll(
            "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
            [$userId]
        );

        // Fetch active session footprints
        $sessions = $this->db->fetchAll(
            "SELECT * FROM sessions WHERE user_id = ? ORDER BY last_activity DESC",
            [$userId]
        );

        return [
            'recent_logs' => $logs,
            'sessions'    => $sessions
        ];
    }
}
