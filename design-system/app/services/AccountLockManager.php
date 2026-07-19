<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\AuditLogRepository;

/**
 * Account Lockout and Suspension Service
 * Handles user account locking, checking suspension status, and unlocking.
 */
class AccountLockManager {
    private UserRepository $userRepo;
    private AuditLogRepository $auditRepo;

    public function __construct(UserRepository $userRepo, AuditLogRepository $auditRepo) {
        $this->userRepo = $userRepo;
        $this->auditRepo = $auditRepo;
    }

    /**
     * Checks if a user is currently suspended.
     */
    public function isLocked(int $userId): bool {
        $user = $this->userRepo->find($userId);
        return $user !== null && $user['status'] === 'suspended';
    }

    /**
     * Locks a user account (suspends the user).
     */
    public function lock(int $userId, string $reason, string $ip, string $ua): bool {
        $this->userRepo->update($userId, ['status' => 'suspended']);
        
        $this->auditRepo->logSecurityAlert(
            $userId,
            'critical',
            "Account suspended automatically. Reason: " . $reason
        );

        $this->auditRepo->logActivity($userId, 'account_locked', $ip, $ua);
        return true;
    }

    /**
     * Unlocks a user account (reactivates the user).
     */
    public function unlock(int $userId, int $adminId, string $ip, string $ua): bool {
        $this->userRepo->update($userId, ['status' => 'active']);
        
        $this->auditRepo->logActivity(
            $userId,
            'account_unlocked',
            $ip,
            $ua
        );

        return true;
    }
}
