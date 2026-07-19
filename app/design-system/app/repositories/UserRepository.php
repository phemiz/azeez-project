<?php
namespace App\Repositories;

use App\Core\Repository;

/**
 * Repository for Users table operations
 */
class UserRepository extends Repository {
    protected string $table = 'users';

    /**
     * Finds a user by username, joining admin access details.
     */
     public function findByUsername(string $username): ?array {
        return $this->query()
            ->select(['users.id', 'users.username', 'users.email', 'users.phone', 'users.password_hash', 'users.status', 'admins.access_level'])
            ->leftJoin('admins', 'users.id', '=', 'admins.user_id')
            ->where('users.username', '=', $username)
            ->first();
    }

    /**
     * Finds a user by email address.
     */
    public function findByEmail(string $email): ?array {
        return $this->query()
            ->where('email', '=', $email)
            ->first();
    }

    /**
     * Finds a user by GSM phone number.
     */
    public function findByPhone(string $phone): ?array {
        return $this->query()
            ->where('phone', '=', $phone)
            ->first();
    }

    /**
     * Lists all registered user nodes with calculated roles.
     */
    public function getOperatorsList(): array {
        $sql = "SELECT u.id, u.username, u.email, u.status, u.created_at, a.access_level,
                CASE WHEN a.id IS NOT NULL THEN 'admin' ELSE 'user' END as role 
                FROM users u 
                LEFT JOIN admins a ON u.id = a.user_id 
                ORDER BY u.created_at DESC";
        
        $db = \App\Core\Database::getInstance();
        return $db->fetchAll($sql);
    }
}
