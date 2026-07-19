<?php
namespace App\Services;

/**
 * RBAC Role Configuration Manager
 * Defines permission sets mapped to enterprise system roles
 */
class RoleManager {
    private array $roles = [
        'admin' => [
            'encrypt_message',
            'decrypt_message',
            'view_audit_logs',
            'manage_backups',
            'override_user_status',
            'view_system_settings'
        ],
        'user' => [
            'encrypt_message',
            'decrypt_message'
        ]
    ];

    /**
     * Resolves permissions for a target role.
     */
    public function getRolePermissions(string $role): array {
        return $this->roles[strtolower($role)] ?? [];
    }

    /**
     * Verifies if a role has authorization for an operation.
     */
    public function hasPermission(string $role, string $permission): bool {
        $permissions = $this->getRolePermissions($role);
        return in_array($permission, $permissions);
    }
}
