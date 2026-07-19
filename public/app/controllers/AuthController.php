<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Repositories\AuditLogRepository;
use App\Services\FailedLoginManager;
use App\Services\AccountLockManager;
use App\Services\ActivityLogger;
use App\Services\SecurityLogger;
use App\Services\AuthenticationService;
use App\Services\OTPService;
use App\Services\RememberMeService;
use App\Services\EncryptionService;

/**
 * Controller managing secure authentication, multi-factor authorization, and registration
 */
class AuthController extends Controller {
    private AuthenticationService $authService;
    private OTPService $otpService;
    private RememberMeService $rememberMe;
    private ActivityLogger $activityLogger;
    private SecurityLogger $securityLogger;
    private UserRepository $userRepo;
    private \App\Services\AlertService $alertService;

    public function __construct() {
        $this->userRepo = new UserRepository();
        $this->alertService = new \App\Services\AlertService();
        $auditRepo = new AuditLogRepository();
        $failedManager = new FailedLoginManager();
        
        $lockManager = new AccountLockManager($this->userRepo, $auditRepo);
        $this->activityLogger = new ActivityLogger($auditRepo);
        $this->securityLogger = new SecurityLogger($auditRepo);
        
        // Inject dependencies into AuthenticationService
        $this->authService = new AuthenticationService(
            $this->userRepo,
            $failedManager,
            $lockManager,
            $this->activityLogger,
            $this->securityLogger
        );

        $this->otpService = new OTPService();
        
        $crypto = new EncryptionService();
        $this->rememberMe = new RememberMeService($crypto, $this->userRepo);
    }

    public function showRegister(): void {
        if (Session::has('user') && Session::get('mfa_verified') === true) {
            $this->redirect(APP_URL . '/dashboard');
        }
        $this->view('auth/register', ['title' => 'Register']);
    }

    public function register(): void {
        $username = $this->getPost('username');
        $email = $this->getPost('email');
        $phone = $this->getPost('phone');
        $password = $this->getPost('password');
        $confirmPassword = $this->getPost('confirm_password');
        $terms = $this->getPost('terms');

        if (!$terms) {
            $this->json(['status' => 'error', 'message' => 'You must accept the security terms and conditions.'], 400);
        }

        if ($password !== $confirmPassword) {
            $this->json(['status' => 'error', 'message' => 'Passcode and Confirm Passcode do not match.'], 400);
        }

        try {
            // Reusable validation check
            $validator = new \App\Core\Validator();
            $validator->validate($_POST, [
                'username' => 'required|username',
                'email'    => 'required|email',
                'phone'    => 'required|phone',
                'password' => 'required|password'
            ]);

            // Duplicate checks
            if ($this->userRepo->findByUsername($username)) {
                $this->json(['status' => 'error', 'message' => 'Username is already registered.'], 400);
            }

            if ($this->userRepo->findByEmail($email)) {
                $this->json(['status' => 'error', 'message' => 'Email address is already registered.'], 400);
            }

            if ($this->userRepo->findByPhone($phone)) {
                $this->json(['status' => 'error', 'message' => 'GSM Phone number is already registered.'], 400);
            }

            $passwordHash = \App\Core\PasswordManager::hash($password);

            $newUserId = $this->userRepo->create([
                'username'      => $username,
                'email'         => $email,
                'phone'         => $phone,
                'password_hash' => $passwordHash,
                'status'        => 'active'
            ]);

            $this->activityLogger->log('register_success', $newUserId);
            $this->securityLogger->logAlert('low', "New operator node registered: {$username}", $newUserId);

            $this->json(['status' => 'success', 'message' => 'Operator node registered. Redirecting to login...']);
        } catch (\App\Core\ValidationException $e) {
            $errors = $e->getErrors();
            $firstErrorField = array_key_first($errors);
            $firstErrorMessage = $errors[$firstErrorField][0] ?? 'Validation failed.';
            $this->json(['status' => 'error', 'message' => $firstErrorMessage], 400);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Registration failed due to a system error.'], 500);
        }
    }

    public function showLogin(): void {
        if (Session::has('user') && Session::get('mfa_verified') === true) {
            $this->redirect(APP_URL . '/dashboard');
        }
        $this->view('auth/login', ['title' => 'Secure Portal Login']);
    }

    public function login(): void {
        $username = $this->getPost('username');
        $password = $this->getPost('password');
        $remember = (bool)$this->getPost('remember', false);

        if (empty($username) || empty($password)) {
            $this->json(['status' => 'error', 'message' => 'Credentials cannot be empty.'], 400);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Heuristics User Agent parsing for Browser & Device
        $browser = 'Unknown Browser';
        $device = 'Desktop';

        if (preg_match('/chrome/i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox/i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $ua)) {
            $browser = 'Safari';
        } elseif (preg_match('/edge/i', $ua)) {
            $browser = 'Edge';
        }

        if (preg_match('/mobile|android|iphone|ipad|ipod/i', $ua)) {
            $device = 'Mobile';
        }

        try {
            // Authenticate user via security service
            $user = $this->authService->authenticate($username, $password, $ip, $ua);

            // Rotate session identifier to block Session Fixation vulnerabilities
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }

            // Configure Session states
            Session::set('user', $user);
            Session::set('mfa_verified', false); // Require OTP completion

            // Trigger OTP generation and capture
            $otp = $this->otpService->generateOTP($user['id']);

            // Save OTP in session temporarily for dev simulation display
            Session::set('simulated_otp', $otp);

            // Handle Remember Me token cookie
            if ($remember) {
                $this->rememberMe->issueToken($user['id']);
            }

            // Check for new device, suspicious IP, multiple sessions
            $this->alertService->checkNewDevice($user['id'], $ua);
            $this->alertService->checkSuspiciousIP($user['id'], $ip);
            $this->alertService->checkMultipleSessions($user['id']);

            // Log details with device and browser parameters
            $this->activityLogger->log("login_success (Device: {$device}, Browser: {$browser})", $user['id']);

            $this->json([
                'status' => 'success', 
                'message' => 'Credentials verified. OTP sent.',
                'redirect' => APP_URL . '/otp'
            ]);
        } catch (\Exception $e) {
            // Check if multiple failed login attempts indicate a brute force attempt
            $this->alertService->checkFailedLogins($username, $ip);
            
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 401);
        }
    }

    public function showAdminLogin(): void {
        if (Session::has('user') && Session::get('mfa_verified') === true) {
            $user = Session::get('user');
            if ($user['role'] === 'admin') {
                $this->redirect(APP_URL . '/admin');
            } else {
                $this->redirect(APP_URL . '/dashboard');
            }
        }
        $this->view('auth/admin_login', ['title' => 'Admin Secure Terminal']);
    }

    public function adminLogin(): void {
        $username = $this->getPost('username');
        $password = $this->getPost('password');
        $remember = (bool)$this->getPost('remember', false);

        if (empty($username) || empty($password)) {
            $this->json(['status' => 'error', 'message' => 'Credentials cannot be empty.'], 400);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Parse user-agent
        $browser = 'Unknown Browser';
        $device = 'Desktop';
        if (preg_match('/chrome/i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/firefox/i', $ua)) $browser = 'Firefox';
        elseif (preg_match('/safari/i', $ua)) $browser = 'Safari';
        elseif (preg_match('/edge/i', $ua)) $browser = 'Edge';

        if (preg_match('/mobile|android|iphone|ipad/i', $ua)) {
            $device = 'Mobile';
        }

        try {
            $user = $this->authService->authenticate($username, $password, $ip, $ua);

            // Verify administrator entry in admins table
            $db = \App\Core\Database::getInstance();
            $isAdmin = (bool)$db->fetchColumn(
                "SELECT COUNT(*) FROM admins WHERE user_id = ?",
                [$user['id']]
            );

            if (!$isAdmin) {
                $this->securityLogger->logAlert('high', "Access Denied: Non-admin account '{$username}' attempted login via Admin Terminal.", $user['id']);
                $this->json(['status' => 'error', 'message' => 'Access denied. Insufficient administrative privileges.'], 403);
            }

            // Set role to admin in user session
            $user['role'] = 'admin';

            // Rotate session ID
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }

            Session::set('user', $user);
            Session::set('mfa_verified', false); // Require OTP completion

            $otp = $this->otpService->generateOTP($user['id']);
            Session::set('simulated_otp', $otp);

            if ($remember) {
                $this->rememberMe->issueToken($user['id']);
            }

            // Check for new device, suspicious IP, multiple sessions
            $this->alertService->checkNewDevice($user['id'], $ua);
            $this->alertService->checkSuspiciousIP($user['id'], $ip);
            $this->alertService->checkMultipleSessions($user['id']);

            $this->activityLogger->log("admin_login_success (Device: {$device}, Browser: {$browser})", $user['id']);

            $this->json([
                'status' => 'success', 
                'message' => 'Admin credentials verified. OTP sent.',
                'redirect' => APP_URL . '/otp'
            ]);
        } catch (\Exception $e) {
            $this->alertService->checkFailedLogins($username, $ip);
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 401);
        }
    }

    public function showOTP(): void {
        $user = Session::get('user');
        if (!$user) {
            $this->redirect(APP_URL . '/login');
        }
        if (Session::get('mfa_verified') === true) {
            $this->redirect(APP_URL . '/dashboard');
        }

        $simulatedOtp = Session::get('simulated_otp');
        $this->view('auth/otp', [
            'title' => 'MFA Verification Required',
            'simulated_otp' => $simulatedOtp
        ]);
    }

    public function verifyOTP(): void {
        $user = Session::get('user');
        if (!$user) {
            $this->json(['status' => 'error', 'message' => 'Session expired.'], 401);
        }

        $code = $this->getPost('otp_code');
        if (empty($code)) {
            $this->json(['status' => 'error', 'message' => 'OTP code is required.'], 400);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        if ($this->otpService->verifyOTP($user['id'], $code)) {
            // Mark MFA verified
            Session::set('mfa_verified', true);
            Session::remove('simulated_otp');

            // Log successful MFA
            $this->activityLogger->log('mfa_success', $user['id']);

            $this->json([
                'status' => 'success',
                'message' => 'Verification complete.',
                'redirect' => APP_URL . '/dashboard'
            ]);
        } else {
            // Log failed MFA
            $this->securityLogger->logAlert('medium', "Failed MFA OTP Verification challenge.", $user['id']);

            $this->json(['status' => 'error', 'message' => 'Incorrect or expired OTP verification code.'], 400);
        }
    }

    public function resendOTP(): void {
        $user = Session::get('user');
        if (!$user) {
            $this->json(['status' => 'error', 'message' => 'Session expired.'], 401);
        }

        try {
            $otp = $this->otpService->generateOTP($user['id']);
            Session::set('simulated_otp', $otp);

            $this->activityLogger->log('otp_resend', $user['id']);

            $this->json([
                'status' => 'success',
                'message' => 'New verification signal transmitted.',
                'simulated_otp' => $otp
            ]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Failed to generate new security token.'], 500);
        }
    }

    public function logout(): void {
        if (Session::has('user')) {
            $user = Session::get('user');
            $this->activityLogger->log('logout', $user['id']);
        }
        
        // Clear Remember Me tokens
        $this->rememberMe->clearToken();
        
        Session::destroy();
        $this->redirect(APP_URL . '/login');
    }

    public function showForgotPassword(): void {
        $this->view('auth/forgot_password', ['title' => 'Recover Portal Passcode']);
    }

    public function forgotPassword(): void {
        $email = $this->getPost('email');
        if (empty($email)) {
            $this->json(['status' => 'error', 'message' => 'Email address is required.'], 400);
        }

        try {
            $validator = new \App\Core\Validator();
            $validator->validate($_POST, [
                'email' => 'required|email'
            ]);

            $user = $this->userRepo->findByEmail($email);
            $successMsg = 'If the email is enrolled, a recovery link has been transmitted.';

            if ($user) {
                // Generate secure random token
                $token = \App\Core\Security::generateToken(32);
                $tokenHash = hash('sha256', $token);
                $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour validity

                // Save token in DB using prepared statements
                $db = \App\Core\Database::getInstance();
                // Clear any pre-existing reset tokens for this user first
                $db->query("DELETE FROM password_resets WHERE user_id = ?", [$user['id']]);
                
                $db->query(
                    "INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)",
                    [$user['id'], $tokenHash, $expiry]
                );

                // Save simulated reset URL in session so the developer can click it in browser
                Session::set('simulated_reset_link', APP_URL . '/reset-password?token=' . $token);
                
                $this->activityLogger->log('password_reset_requested', $user['id']);
                $this->securityLogger->logAlert('low', "Passcode reset token issued for operator.", $user['id']);
            }

            $this->json(['status' => 'success', 'message' => $successMsg]);
        } catch (\App\Core\ValidationException $e) {
            $errors = $e->getErrors();
            $firstErrorMessage = $errors['email'][0] ?? 'Validation failed.';
            $this->json(['status' => 'error', 'message' => $firstErrorMessage], 400);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'System error processing request.'], 500);
        }
    }

    public function showResetPassword(): void {
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            $this->redirect(APP_URL . '/login');
        }

        $tokenHash = hash('sha256', $token);
        $db = \App\Core\Database::getInstance();
        $reset = $db->fetch(
            "SELECT r.*, u.username FROM password_resets r 
             INNER JOIN users u ON r.user_id = u.id 
             WHERE r.token_hash = ? AND r.expires_at > NOW()",
            [$tokenHash]
        );

        if (!$reset) {
            // Invalid or expired token
            $this->view('auth/forgot_password', [
                'title' => 'Recover Portal Passcode', 
                'error' => 'The recovery link is invalid or has expired.'
            ]);
            return;
        }

        $this->view('auth/reset_password', [
            'title' => 'Reset Terminal Passcode',
            'token' => $token,
            'username' => $reset['username']
        ]);
    }

    public function resetPassword(): void {
        $token = $this->getPost('token');
        $password = $this->getPost('password');
        $confirmPassword = $this->getPost('confirm_password');

        if (empty($token) || empty($password) || empty($confirmPassword)) {
            $this->json(['status' => 'error', 'message' => 'All parameters are required.'], 400);
        }

        if ($password !== $confirmPassword) {
            $this->json(['status' => 'error', 'message' => 'Passcode and Confirm Passcode do not match.'], 400);
        }

        try {
            // Reusable password strength check
            $validator = new \App\Core\Validator();
            $validator->validate($_POST, [
                'password' => 'required|password'
            ]);

            $tokenHash = hash('sha256', $token);
            $db = \App\Core\Database::getInstance();
            $reset = $db->fetch(
                "SELECT * FROM password_resets WHERE token_hash = ? AND expires_at > NOW()",
                [$tokenHash]
            );

            if (!$reset) {
                $this->json(['status' => 'error', 'message' => 'Recovery link is invalid or expired.'], 400);
            }

            // Update user password hash
            $newHash = \App\Core\PasswordManager::hash($password);
            $this->userRepo->update($reset['user_id'], ['password_hash' => $newHash]);

            // Invalidate/delete reset token
            $db->query("DELETE FROM password_resets WHERE user_id = ?", [$reset['user_id']]);

            // Clear developer simulation link
            Session::remove('simulated_reset_link');

            // Log successful resets
            $this->activityLogger->log('password_reset_success', $reset['user_id']);
            $this->securityLogger->logAlert('low', "Passcode updated successfully via reset token.", $reset['user_id']);

            $this->json([
                'status' => 'success',
                'message' => 'Cipher passcode updated successfully. Redirecting to login...',
                'redirect' => APP_URL . '/login'
            ]);
        } catch (\App\Core\ValidationException $e) {
            $errors = $e->getErrors();
            $firstErrorMessage = $errors['password'][0] ?? 'Passcode strength check failed.';
            $this->json(['status' => 'error', 'message' => $firstErrorMessage], 400);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Failed to reset passcode.'], 500);
        }
    }
}
