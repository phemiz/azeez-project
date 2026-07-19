<?php
namespace App\Middleware;

use App\Core\Session;
use App\Services\RememberMeService;
use App\Repositories\UserRepository;
use App\Services\EncryptionService;

use App\Core\MiddlewareInterface;

/**
 * Authentication Gate Middleware
 * Verifies session presence, triggers auto-login cookies, and enforces MFA checkpoints
 */
class AuthenticationMiddleware implements MiddlewareInterface {
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

        // Strip the APP_URL base path (e.g. /gsm-security/public) so we
        // compare clean route paths like /otp, /login, /dashboard, etc.
        $basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        if ($uri === '' || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

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
                Session::set('login_method', 'remember_me');
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
