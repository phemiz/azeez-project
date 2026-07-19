<?php
namespace App\Middleware;

use App\Core\Session;

use App\Core\MiddlewareInterface;

/**
 * Authentication and Multi-Factor/Role Access Control Middleware
 */
class AuthMiddleware implements MiddlewareInterface {
    public function handle(callable $next): void {
        // Ensure session is started
        Session::start();

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = '/' . trim($uri, '/');

        // Check if user session exists
        if (!Session::has('user')) {
            // Prevent infinite redirection loops
            if ($uri !== '/login' && $uri !== '/register') {
                $this->denyAccess();
            }
            $next();
            return;
        }

        $user = Session::get('user');
        $mfaVerified = Session::get('mfa_verified', false);

        // User is logged in but has not verified the OTP challenge
        if (!$mfaVerified) {
            // Only allow OTP submission and Logout endpoints
            $allowedUnverified = ['/otp', '/logout', '/verify-otp', '/resend-otp'];
            if (!in_array($uri, $allowedUnverified)) {
                $this->redirect(APP_URL . '/otp');
            }
            $next();
            return;
        }

        // Handle Role-Based Access Control (RBAC)
        // If route is /admin or /admin/*, verify if role is admin
        if (strpos($uri, '/admin') === 0 && $user['role'] !== 'admin') {
            http_response_code(403);
            exit("<h1>403 Forbidden</h1><p>You do not have permission to access this administrative resource.</p>");
        }

        $next();
    }

    private function denyAccess(): void {
        if ($this->isJsonRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized. Session expired or missing.'
            ]);
            exit;
        }
        $this->redirect(APP_URL . '/login');
    }

    private function redirect(string $url): void {
        header("Location: " . $url);
        exit;
    }

    private function isJsonRequest(): bool {
        return isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    }
}
