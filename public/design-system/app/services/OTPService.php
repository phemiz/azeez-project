<?php
namespace App\Services;

use App\Core\Database;

/**
 * OTP Verification Service
 * Handles secure generation, storage (hashed), and verification of One-Time Passwords
 */
class OTPService {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Generates a 6-digit OTP code, hashes it, and stores it in the database.
     * In development mode, the OTP is returned in the result so it can be simulated/displayed.
     * 
     * @param int $userId Target User ID
     * @return string The raw 6-digit OTP code
     */
    public function generateOTP(int $userId): string {
        // 1. Generate secure 6-digit code
        $rawCode = (string)random_int(100000, 999999);

        // 2. Hash the code for database storage (defends against DB leaks)
        $codeHash = password_hash($rawCode, PASSWORD_DEFAULT);

        // 3. Set expiration window (5 minutes from now)
        $expiresAt = date('Y-m-d H:i:s', time() + 300);

        // 4. Invalidate any existing active OTPs for this user (prevent multi-OTP exploits)
        $this->db->query(
            "UPDATE otp_codes SET verified = 1 WHERE user_id = ? AND verified = 0",
            [$userId]
        );

        // 5. Store the new OTP
        $this->db->query(
            "INSERT INTO otp_codes (user_id, code_hash, expires_at) VALUES (?, ?, ?)",
            [$userId, $codeHash, $expiresAt]
        );

        return $rawCode;
    }

    /**
     * Verifies a user-supplied OTP code.
     * 
     * @param int $userId Target User ID
     * @param string $rawCode Code entered by the user
     * @return bool True if valid and verified, false otherwise
     */
    public function verifyOTP(int $userId, string $rawCode): bool {
        // Fetch active, unexpired OTP codes for the user
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT id, code_hash FROM otp_codes 
                WHERE user_id = ? AND verified = 0 AND expires_at > ? 
                ORDER BY created_at DESC LIMIT 1";
        
        $otpRecord = $this->db->fetch($sql, [$userId, $now]);

        if (!$otpRecord) {
            return false;
        }

        // Verify the code securely (timing-attack safe)
        if (password_verify($rawCode, $otpRecord['code_hash'])) {
            // Mark as verified to prevent reuse (replay attacks)
            $this->db->query(
                "UPDATE otp_codes SET verified = 1 WHERE id = ?",
                [$otpRecord['id']]
            );
            return true;
        }

        return false;
    }
}
