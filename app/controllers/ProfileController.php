<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\ProfileService;
use App\Services\ActivityLogger;
use App\Repositories\AuditLogRepository;
use App\Core\Validator;
use App\Core\ValidationException;
use App\Repositories\UserRepository;

/**
 * Stateful User Profile Controller
 * Orchestrates personal details editing, credentials rotations, avatar uploads,
 * active device session indicators, and security settings.
 */
class ProfileController extends Controller {
    private ProfileService $profileService;
    private ActivityLogger $activityLogger;

    public function __construct() {
        $this->profileService = new ProfileService();
        $auditRepo = new AuditLogRepository();
        $this->activityLogger = new ActivityLogger($auditRepo);
    }

    /**
     * Renders the user profile center
     */
    public function index(): void {
        $user = Session::get('user');
        
        // Fetch fresh db details for operator profile
        $db = \App\Core\Database::getInstance();
        $freshUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$user['id']]);

        // Get sessions & logs list
        $securityData = $this->profileService->getProfileSecurityDetails($user['id']);

        $this->view('user/profile', [
            'title'       => 'Operator Profile Center',
            'user'        => $freshUser,
            'recentLogs'  => $securityData['recent_logs'],
            'sessions'    => $securityData['sessions']
        ]);
    }

    /**
     * Updates personal operator profile coordinates
     */
    public function update(): void {
        $user = Session::get('user');
        $email = $this->getPost('email');
        $phone = $this->getPost('phone');

        try {
            $validator = new Validator();
            $validator->validate($_POST, [
                'email' => 'required|email',
                'phone' => 'required|phone'
            ]);

            // Duplicate checks
            $userRepo = new UserRepository();
            $existingEmail = $userRepo->findByEmail($email);
            if ($existingEmail && $existingEmail['id'] != $user['id']) {
                $this->json(['status' => 'error', 'message' => 'Email coordinate is already registered by another operator.'], 400);
            }

            $existingPhone = $userRepo->findByPhone($phone);
            if ($existingPhone && $existingPhone['id'] != $user['id']) {
                $this->json(['status' => 'error', 'message' => 'Phone coordinate is already registered by another operator.'], 400);
            }

            $this->profileService->updateProfile($user['id'], $email, $phone);
            
            // Sync session variables
            $user['email'] = $email;
            $user['phone'] = $phone;
            Session::set('user', $user);

            $this->activityLogger->log("profile_update (Email: {$email}, Phone: {$phone})", $user['id']);
            $this->json(['status' => 'success', 'message' => 'Profile coordinates updated successfully.']);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $firstField = array_key_first($errors);
            $firstMsg = $errors[$firstField][0] ?? 'Validation failed.';
            $this->json(['status' => 'error', 'message' => $firstMsg], 400);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Overwrites operator passcode credentials
     */
    public function changePassword(): void {
        $user = Session::get('user');
        $oldPassword = $this->getPost('old_password');
        $newPassword = $this->getPost('new_password');

        try {
            $validator = new Validator();
            $validator->validate($_POST, [
                'old_password' => 'required',
                'new_password' => 'required|password'
            ]);

            $this->profileService->changePassword($user['id'], $oldPassword, $newPassword);
            $this->activityLogger->log("password_change_success", $user['id']);
            $this->json(['status' => 'success', 'message' => 'Passcode rotated successfully.']);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $firstField = array_key_first($errors);
            $firstMsg = $errors[$firstField][0] ?? 'Validation failed.';
            $this->json(['status' => 'error', 'message' => $firstMsg], 400);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Updates security preference settings
     */
    public function updateSecuritySettings(): void {
        $user = Session::get('user');
        $mfaEnabled = $this->getPost('mfa_enabled') !== null ? 1 : 0;
        $loginNotify = $this->getPost('login_notify') !== null ? 1 : 0;
        $sessionTimeout = (int)$this->getPost('session_timeout', 900);

        // Validation for session timeout
        $allowedTimeouts = [300, 900, 1800, 3600];
        if (!in_array($sessionTimeout, $allowedTimeouts)) {
            $sessionTimeout = 900;
        }

        try {
            $this->profileService->updateSecuritySettings($user['id'], $mfaEnabled, $loginNotify, $sessionTimeout);
            
            // Sync custom timeout into current session user configuration
            $user['session_timeout_custom'] = $sessionTimeout;
            Session::set('user', $user);

            $this->activityLogger->log("security_settings_update (MFA: {$mfaEnabled}, Alerts: {$loginNotify}, Timeout: {$sessionTimeout}s)", $user['id']);
            $this->json(['status' => 'success', 'message' => 'Security preferences updated successfully.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Terminates another active device session footprints
     */
    public function revokeSession(): void {
        $user = Session::get('user');
        $sessionId = $this->getPost('session_id');

        if (empty($sessionId)) {
            $this->json(['status' => 'error', 'message' => 'Invalid session identifier.'], 400);
        }

        // Prevent self-revocation from this endpoint
        if ($sessionId === session_id()) {
            $this->json(['status' => 'error', 'message' => 'Cannot revoke current active session.'], 400);
        }

        try {
            $this->profileService->revokeSession($user['id'], $sessionId);
            $this->activityLogger->log("session_revoked (Session ID: " . substr($sessionId, 0, 8) . "...)", $user['id']);
            $this->json(['status' => 'success', 'message' => 'Active session revoked successfully.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Uploads simulated avatar profile picture
     */
    public function uploadAvatar(): void {
        $user = Session::get('user');
        
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['status' => 'error', 'message' => 'Invalid file uploaded.'], 400);
        }

        $file = $_FILES['avatar'];

        // Limit size to 2MB
        if ($file['size'] > 2 * 1024 * 1024) {
            $this->json(['status' => 'error', 'message' => 'File size exceeds standard 2MB limit.'], 400);
        }

        // 1. Verify actual MIME type using server-side binary inspections
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($realMimeType, $allowedMimeTypes)) {
            $this->json(['status' => 'error', 'message' => 'Invalid image structure. Only JPEG, PNG, and GIF files are allowed.'], 400);
        }

        // 2. Resolve target file extension from the real MIME type
        $extension = 'jpg';
        if ($realMimeType === 'image/png') {
            $extension = 'png';
        } elseif ($realMimeType === 'image/gif') {
            $extension = 'gif';
        }

        // 3. Generate completely random, secure destination name
        $filename = 'avatar_' . $user['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $extension;

        $uploadDir = dirname(dirname(__DIR__)) . '/public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $destPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $avatarUrl = APP_URL . '/uploads/' . $filename;
            $this->profileService->updateAvatar($user['id'], $avatarUrl);

            // Sync session
            $user['avatar'] = $avatarUrl;
            Session::set('user', $user);

            $this->activityLogger->log("profile_avatar_upload", $user['id']);
            $this->json(['status' => 'success', 'message' => 'Avatar profile updated.', 'avatar_url' => $avatarUrl]);
        } else {
            $this->json(['status' => 'error', 'message' => 'Failed to save avatar image file.'], 500);
        }
    }
}
