<?php
namespace App\Models;

use App\Core\Model;
use App\Repositories\UserRepository;

/**
 * User Entity Model
 * Proxies operations to UserRepository using Repository Pattern and QueryBuilder
 */
class User extends Model {
    private UserRepository $repo;

    public function __construct() {
        parent::__construct();
        $this->repo = new UserRepository();
    }

    /**
     * Finds user by username.
     */
    public function findByUsername(string $username): ?array {
        return $this->repo->findByUsername($username);
    }

    /**
     * Lists all registered user nodes with calculated roles.
     */
    public function getAllUsers(): array {
        return $this->repo->getOperatorsList();
    }

    /**
     * Toggles user account status.
     */
    public function updateStatus(int $userId, string $status): bool {
        return $this->repo->update($userId, ['status' => $status]);
    }

    /**
     * Creates new operator account.
     */
    public function create(string $username, string $email, string $passwordHash): int {
        return $this->repo->create([
            'username'      => $username,
            'email'         => $email,
            'password_hash' => $passwordHash,
            'status'        => 'active'
        ]);
    }
}
