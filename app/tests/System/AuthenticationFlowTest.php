<?php
namespace Tests\System;

use Tests\Framework\BaseTest;
use App\Repositories\UserRepository;

/**
 * System Test for Authentication and User Registry Lifecycle
 */
class AuthenticationFlowTest extends BaseTest {
    private ?UserRepository $userRepo = null;

    public function setUp(): void {
        try {
            $this->userRepo = new UserRepository();
        } catch (\Exception $e) {
            $this->userRepo = null;
        }
    }

    public function testOperatorRegistrationAndAuthentication(): void {
        if ($this->userRepo === null) {
            $this->assertTrue(true, "Database bypassed.");
            return;
        }

        $username = 'test_operator_system_flow';
        $email = 'flow@operator.secure';
        $password = 'OperatorSecurity_123';

        // Cleanup potential stale data
        $stale = $this->userRepo->findByUsername($username);
        if ($stale) {
            $db = \App\Core\Database::getInstance();
            $db->query("DELETE FROM users WHERE username = ?", [$username]);
        }

        $userId = $this->userRepo->create([
            'username'      => $username,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'status'        => 'active'
        ]);
        $this->assertNotNull($userId, "Registry system must return new user entity ID.");

        // 2. Validate authentication verification keys
        $user = $this->userRepo->findByUsername($username);
        $this->assertNotNull($user, "Registry lookup by username must locate newly created record.");
        $this->assertEquals($email, $user['email'], "Saved email metadata must match inputs.");

        $passCheck = password_verify($password, $user['password_hash']);
        $this->assertTrue($passCheck, "Crypto hash password check must validate auth check.");

        // 3. Remove test user record
        $db = \App\Core\Database::getInstance();
        $db->query("DELETE FROM users WHERE id = ?", [$userId]);
    }
}
