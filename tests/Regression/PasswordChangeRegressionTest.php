<?php
namespace Tests\Regression;

use Tests\Framework\BaseTest;

/**
 * Regression Test verifying Password Hashing stability
 */
class PasswordChangeRegressionTest extends BaseTest {
    public function testPasswordHashVerificationFlow(): void {
        $password = 'RegressionSecureTestPassword_123';
        
        // 1. Generate hash
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        $this->assertNotNull($hash, "Password hashing function must return valid hash string.");

        // 2. Validate matching credentials
        $match = password_verify($password, $hash);
        $this->assertTrue($match, "Verifying original password against generated hash must succeed.");

        // 3. Validate wrong credentials
        $wrongMatch = password_verify('WrongPassword_999', $hash);
        $this->assertFalse($wrongMatch, "Verifying incorrect password against generated hash must fail.");
    }
}
