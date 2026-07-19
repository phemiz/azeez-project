<?php
namespace App\Core;

/**
 * Enterprise Password Manager
 * Enforces secure password complexity rules, validates hashes, and monitors re-hash demands.
 */
class PasswordManager {
    /**
     * Hashes password securely using standard bcrypt/argon algorithms.
     */
    public static function hash(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifies password against hash securely.
     */
    public static function verify(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Checks if password hash needs updating to a stronger algorithm configuration.
     */
    public static function needsRehash(string $hash): bool {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * Enforces complex password constraints (Entropy Validation).
     * 
     * Rules: Minimum 8 characters, at least 1 uppercase letter, 
     * 1 lowercase letter, 1 digit, and 1 special symbol.
     * 
     * @param string $password
     * @return bool True if strong, false otherwise.
     */
    public static function validateStrength(string $password): bool {
        if (strlen($password) < 8) {
            return false;
        }

        // Check for uppercase
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Check for lowercase
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Check for numbers
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Check for special characters
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return false;
        }

        return true;
    }
}
