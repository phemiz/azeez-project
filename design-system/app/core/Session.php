<?php
namespace App\Core;

/**
 * Enterprise Session & CSRF Manager
 * Implements session hijacking mitigation and secure anti-CSRF token verification
 */
class Session {
    /**
     * Initializes and configures a secure session environment.
     */
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Apply security directives to session configuration
            ini_set('session.use_only_cookies', 1);
            ini_set('session.use_strict_mode', 1);
            
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => SESSION_SECURE,
                'httponly' => SESSION_HTTPONLY,
                'samesite' => SESSION_SAMESITE
            ]);

            session_start();
        }

        // Enforce session expiration timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            self::destroy();
            self::redirectToLogin();
        }
        $_SESSION['last_activity'] = time();

        // Regenerate session ID periodically to prevent session fixation attacks
        if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        } elseif (time() - $_SESSION['created_at'] > 300) { // Every 5 minutes
            session_regenerate_id(true);
            $_SESSION['created_at'] = time();
        }
    }

    public static function set(string $key, $value): void {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Generates and stores a unique CSRF token in the session.
     */
    public static function generateCSRFToken(): string {
        if (!self::has('csrf_token')) {
            self::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('csrf_token');
    }

    /**
     * Verifies a user-supplied token against the stored session CSRF token.
     */
    public static function verifyCSRFToken(string $token): bool {
        $stored = self::get('csrf_token');
        if (!$stored) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    private static function redirectToLogin(): void {
        header("Location: " . APP_URL . "/login");
        exit;
    }
}
