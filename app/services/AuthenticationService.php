<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Core\PasswordManager;

/**
 * Enterprise Authentication orchestrator
 * Regulates operator credential validations, failed attempts auditing, account locks,
 * and session state configurations.
 */
class AuthenticationService {
    private UserRepository $userRepo;
    private FailedLoginManager $failedManager;
    private AccountLockManager $lockManager;
    private ActivityLogger $activityLogger;
    private SecurityLogger $securityLogger;

    public function __construct(
        UserRepository $userRepo,
        FailedLoginManager $failedManager,
        AccountLockManager $lockManager,
        ActivityLogger $activityLogger,
        SecurityLogger $securityLogger
    ) {
        $this->userRepo = $userRepo;
        $this->failedManager = $failedManager;
        $this->lockManager = $lockManager;
        $this->activityLogger = $activityLogger;
        $this->securityLogger = $securityLogger;
    }

    /**
     * Validates credentials and checks security lockout rules.
     * 
     * @throws \Exception
     */
    public function authenticate(string $username, string $password, string $ip, string $ua): array {
        // 1. Check IP or Account lock velocity limit
        if ($this->failedManager->hasExceededLimit($username, $ip)) {
            $this->securityLogger->logAlert('critical', "Login blocked due to rate-limit lockouts. Username: {$username}");
            throw new \Exception("Suspicious Activity Detected. Account Temporarily Locked. Administrator Notified.");
        }

        // 2. Fetch User
        $user = $this->userRepo->findByUsername($username);

        if (!$user) {
            // Perform dummy verification to prevent timing side-channel username enumeration
            PasswordManager::verify($password, '$2y$10$U2e.2rZ/zI.d3.3BqX44uODCg1g8x7.FhX15tVv8n0W9d8s1hXnGe');

            // Track failure
            $this->failedManager->recordAttempt($username, $ip, 'failed');
            $this->securityLogger->logAlert('medium', "Failed login attempt for non-existent user: {$username}");
            throw new \Exception("Invalid username or passcode.");
        }

        // 3. Check Lockout State
        if ($this->lockManager->isLocked($user['id'])) {
            $this->securityLogger->logAlert('high', "Suspended account login attempt: {$username}", $user['id']);
            throw new \Exception("Suspicious Activity Detected. Account Temporarily Locked. Administrator Notified.");
        }

        // 4. Verify Credentials
        if (PasswordManager::verify($password, $user['password_hash'])) {
            // Success: Reset rate counters
            $this->failedManager->resetAttempts($username, $ip);
            $this->failedManager->recordAttempt($username, $ip, 'success');

            // Log activity
            $this->activityLogger->log('login_success', $user['id']);

            // Secure Rehash verification
            if (PasswordManager::needsRehash($user['password_hash'])) {
                $newHash = PasswordManager::hash($password);
                $this->userRepo->update($user['id'], ['password_hash' => $newHash]);
                $this->securityLogger->logAlert('low', "User passcode hash updated to match stronger cipher configurations.", $user['id']);
            }

            // Return safe credentials profile (exclude password hash)
            return [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['access_level'] ? 'admin' : 'user'
            ];
        } else {
            // Failure: Record attempt
            $this->failedManager->recordAttempt($username, $ip, 'failed');
            $this->securityLogger->logAlert('medium', "Passcode verification failure for operator: {$username}", $user['id']);

            // If threshold reached, apply suspension
            if ($this->failedManager->hasExceededLimit($username, $ip)) {
                $this->lockManager->lock($user['id'], "Exceeded maximum failed attempts window", $ip, $ua);
                throw new \Exception("Suspicious Activity Detected. Account Temporarily Locked. Administrator Notified.");
            }

            throw new \Exception("Invalid username or passcode.");
        }
    }
}
