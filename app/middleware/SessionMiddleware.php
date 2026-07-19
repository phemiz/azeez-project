<?php
namespace App\Middleware;

use App\Core\Session;
use App\Core\MiddlewareInterface;

/**
 * Session Lifecycle Middleware
 * Ensures sessions are started, checks custom operator timeouts, and validates active database session footprints.
 */
class SessionMiddleware implements MiddlewareInterface {
    public function handle(callable $next): void {
        // 1. Ensure Session is bootstrapped
        Session::start();

        // 2. Perform DB validation check for logged-in operator nodes
        if (Session::has('user')) {
            $sessionId = session_id();
            if (!Session::isSessionValid($sessionId)) {
                Session::destroy();
                
                // Return structured error for API requests, redirect for web requests
                if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                    http_response_code(401);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status'  => 'error',
                        'message' => 'Your active session footprint has been terminated by an administrator.'
                    ]);
                    exit;
                }
                header("Location: " . APP_URL . "/login");
                exit;
            }

            // Sync session variables back to the database sessions table
            Session::writeSessionToDb();
        }

        // 3. Clean up expired DB sessions probabilistically (1% chance of executing on any request)
        if (random_int(1, 100) === 1) {
            try {
                $db = \App\Core\Database::getInstance();
                $expireTime = time() - SESSION_LIFETIME;
                $db->query("DELETE FROM sessions WHERE last_activity < ?", [$expireTime]);
            } catch (\Exception $e) {
                error_log("Database session garbage collection failed: " . $e->getMessage());
            }
        }

        $next();
    }
}
