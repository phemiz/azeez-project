<?php
namespace App\Middleware;

use App\Core\Session;
use App\Services\RememberMeService;
use App\Repositories\UserRepository;
use App\Services\EncryptionService;

/**
 * Authentication Gate Middleware
 * Verifies session presence, triggers auto-login cookies, and enforces MFA checkpoints
 */
class AuthenticationMiddleware {
    private RememberMeService $rememberMe;

    public function __construct() {
        $userRepo = new UserRepository();
        $crypto = new EncryptionService();
        $this->rememberMe = new RememberMeService($crypto, $userRepo);
    }

    public function handle(callable $next): void {
        Session::start();

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = '/' . trim($uri, '/');

        // 1. If no active session exists, check for Remember Me cookie
        if (!Session::has('user')) {
            $user = $this->rememberMe->autoLogin();
            if ($user) {
                Session::set('user', [
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'email'    => $user['email'],
                    'role'     => $user['access_level'] ? 'admin' : 'user'
                ]);
                Session::set('mfa_verified', true); // Auto-login bypasses MFA for convenience of returning nodes
            }
        }

        // 2. Validate session existence
        if (!Session::has('user')) {
            $allowedEndpoints = ['/login', '/register'];
            if (!in_array($uri, $allowedEndpoints)) {
                $this->denyAccess();
            }
            $next();
            return;
        }

        // 3. Enforce MFA verification checkpoints
        $mfaVerified = Session::get('mfa_verified', false);
        if (!$mfaVerified) {
            $allowedUnverified = ['/otp', '/logout', '/verify-otp', '/resend-otp'];
            if (!in_array($uri, $allowedUnverified)) {
                header("Location: " . APP_URL . "/otp");
                exit;
            }
        }

        $next();
    }

    private function denyAccess(): void {
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized. Authentication credentials missing.'
            ]);
            exit;
        }
        header("Location: " . APP_URL . "/login");
        exit;
    }
}
