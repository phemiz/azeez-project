<?php
namespace App\Services;

/**
 * Enterprise Authorization Permission Evaluator
 * Dictates whether a user entity can invoke a specific capability
 */
class PermissionManager {
    private RoleManager $roleManager;

    public function __construct(RoleManager $roleManager) {
        $this->roleManager = $roleManager;
    }

    /**
     * Determines if a user has access to a capability.
     * 
     * @param array $user Session user profile
     * @param string $permission Target permission
     * @return bool True if authorized, false otherwise
     */
    public function can(array $user, string $permission): bool {
        if (!isset($user['role'])) {
            return false;
        }

        return $this->roleManager->hasPermission($user['role'], $permission);
    }
}
