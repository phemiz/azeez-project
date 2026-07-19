<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\ProfileService;
use App\Services\ActivityLogger;

/**
 * Stateful User Profile Controller
 * Orchestrates personal details editing, credentials rotations, avatar uploads,
 * and active device session indicators.
 */
class ProfileController extends Controller {
    private ProfileService $profileService;
    private ActivityLogger $activityLogger;

    public function __construct() {
        $this->profileService = new ProfileService();
        $this->activityLogger = new ActivityLogger();
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

        if (empty($email) || empty($phone)) {
            $this->json(['status' => 'error', 'message' => 'Email and Phone fields are required.'], 400);
        }

        try {
            $this->profileService->updateProfile($user['id'], $email, $phone);
            
            // Sync session variables
            $user['email'] = $email;
            $user['phone'] = $phone;
            Session::set('user', $user);

            $this->activityLogger->log("profile_update (Email: {$email}, Phone: {$phone})", $user['id']);
            $this->json(['status' => 'success', 'message' => 'Profile coordinates updated successfully.']);
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

        if (empty($oldPassword) || empty($newPassword)) {
            $this->json(['status' => 'error', 'message' => 'Credentials fields are required.'], 400);
        }

        try {
            $this->profileService->changePassword($user['id'], $oldPassword, $newPassword);
            $this->activityLogger->log("password_change_success", $user['id']);
            $this->json(['status' => 'success', 'message' => 'Passcode rotated successfully.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
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
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            $this->json(['status' => 'error', 'message' => 'Only JPEG, PNG, and GIF files are allowed.'], 400);
        }

        // Limit size to 2MB
        if ($file['size'] > 2 * 1024 * 1024) {
            $this->json(['status' => 'error', 'message' => 'File size exceeds standard 2MB limit.'], 400);
        }

        $uploadDir = dirname(dirname(__DIR__)) . '/public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'avatar_' . $user['id'] . '_' . time() . '_' . basename($file['name']);
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
