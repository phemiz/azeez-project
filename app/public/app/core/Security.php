<?php
namespace App\Core;

/**
 * Enterprise Security Utility Class
 * Provides input sanitization, context-aware output escaping, and cryptographically secure token generation.
 */
class Security {
    /**
     * Sanitizes inputs by stripping HTML tags and trimming content
     */
    public static function sanitizeString(string $data): string {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape data for safe output in standard HTML body context
     */
    public static function escapeHtml(string $data): string {
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape data for safe output within HTML attributes
     */
    public static function escapeAttr(string $data): string {
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape data for safe injection inside Inline JavaScript variables
     */
    public static function escapeJs(string $data): string {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Escape and validates URLs to prevent JavaScript protocol injection (XSS)
     */
    public static function escapeUrl(string $url): string {
        $sanitized = filter_var($url, FILTER_SANITIZE_URL);
        
        // Block dangerous schemes
        if (preg_match('/^(javascript|data|vbscript):/i', $sanitized)) {
            return '#';
        }
        
        return self::escapeAttr($sanitized);
    }

    /**
     * Generates a cryptographically secure random token (hex-encoded)
     */
    public static function generateToken(int $bytes = 32): string {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Performs a constant-time comparison of two strings to prevent timing-attacks
     */
    public static function compareTimingSafe(string $known, string $user): bool {
        return hash_equals($known, $user);
    }
}
