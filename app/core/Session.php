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
        $timeout = SESSION_LIFETIME;
        if (self::has('user') && isset($_SESSION['user']['session_timeout_custom'])) {
            $timeout = (int)$_SESSION['user']['session_timeout_custom'];
        }

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::destroy();
            self::redirectToLogin();
        }
        $_SESSION['last_activity'] = time();


        // Regenerate session ID periodically to prevent session fixation attacks
        if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        } elseif (time() - $_SESSION['created_at'] > 300) { // Every 5 minutes
            $oldSessionId = session_id();
            session_regenerate_id(true);
            $newSessionId = session_id();
            $_SESSION['created_at'] = time();

            $_SESSION['rotation_count'] = ($_SESSION['rotation_count'] ?? 0) + 1;

            self::updateSessionIdInDb($oldSessionId, $newSessionId);

            // Log session rotation to activity_logs
            try {
                $db = \App\Core\Database::getInstance();
                $userId = $_SESSION['user']['id'] ?? null;
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $db->query(
                    "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, severity, threat_details) 
                     VALUES (?, 'session_rotated', ?, ?, 'low', ?)",
                    [$userId, $ip, $ua, "Session ID rotated from " . substr($oldSessionId, 0, 8) . "... to " . substr($newSessionId, 0, 8) . "... (Rotation count: " . $_SESSION['rotation_count'] . ")"]
                );
            } catch (\Exception $e) {
                // Ignore DB logging errors during session start to avoid breaking request
            }
        }
    }

    public static function set(string $key, $value): void {
        $_SESSION[$key] = $value;
        if ($key === 'user' || $key === 'mfa_verified') {
            self::writeSessionToDb();
        }
    }

    public static function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        unset($_SESSION[$key]);
        if ($key === 'user') {
            $sessionId = session_id();
            if ($sessionId) {
                self::deleteSessionFromDb($sessionId);
            }
        }
    }

    public static function destroy(): void {
        $sessionId = session_id();
        if ($sessionId) {
            self::deleteSessionFromDb($sessionId);
        }
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
     * Inserts or updates the session record in the database.
     */
    public static function writeSessionToDb(): void {
        $user = self::get('user');
        $userId = $user['id'] ?? null;
        if (!$userId) {
            return;
        }

        try {
            $db = \App\Core\Database::getInstance();
            $id = session_id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $payload = json_encode($_SESSION);
            $lastActivity = time();

            $db->query(
                "INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity) 
                 VALUES (?, ?, ?, ?, ?, ?) 
                 ON DUPLICATE KEY UPDATE user_id = ?, ip_address = ?, user_agent = ?, payload = ?, last_activity = ?",
                [$id, $userId, $ip, $ua, $payload, $lastActivity, $userId, $ip, $ua, $payload, $lastActivity]
            );
        } catch (\Exception $e) {
            error_log("Failed to write session to DB: " . $e->getMessage());
        }
    }

    /**
     * Deletes the session record from the database.
     */
    public static function deleteSessionFromDb(string $sessionId): void {
        try {
            $db = \App\Core\Database::getInstance();
            $db->query("DELETE FROM sessions WHERE id = ?", [$sessionId]);
        } catch (\Exception $e) {
            error_log("Failed to delete session from DB: " . $e->getMessage());
        }
    }

    /**
     * Checks if the session ID exists in the database sessions table.
     */
    public static function isSessionValid(string $sessionId): bool {
        try {
            $db = \App\Core\Database::getInstance();
            $exists = $db->fetchColumn("SELECT COUNT(*) FROM sessions WHERE id = ?", [$sessionId]);
            return (int)$exists > 0;
        } catch (\Exception $e) {
            return true; // Keep session valid in case of database connectivity issues
        }
    }

    /**
     * Updates the session ID in the database sessions table during regeneration.
     */
    private static function updateSessionIdInDb(string $oldId, string $newId): void {
        try {
            $db = \App\Core\Database::getInstance();
            $db->query("UPDATE sessions SET id = ? WHERE id = ?", [$newId, $oldId]);
        } catch (\Exception $e) {
            error_log("Failed to update session ID in DB: " . $e->getMessage());
        }
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
