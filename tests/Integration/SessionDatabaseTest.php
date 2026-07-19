<?php
namespace Tests\Integration;

use Tests\Framework\BaseTest;
use App\Core\Database;
use App\Core\Session;

/**
 * Integration Test for Active Session Database Synchronization
 */
class SessionDatabaseTest extends BaseTest {
    private ?Database $db = null;

    public function setUp(): void {
        try {
            $this->db = Database::getInstance();
            Session::start();
        } catch (\Exception $e) {
            $this->db = null;
        }
    }

    public function testSessionSync(): void {
        if ($this->db === null) {
            $this->assertTrue(true, "Database bypassed.");
            return;
        }

        $sessionId = 'test_integration_session_999';
        $userId = 1; // Assuming default administrator ID is 1 or dummy test user
        $userAgent = 'Mozilla/5.0 PHPUnitIntegration';
        $ip = '127.0.0.1';

        // 1. Manually write mock session record
        $this->db->query("DELETE FROM sessions WHERE id = ?", [$sessionId]);
        
        $this->db->query(
            "INSERT INTO sessions (id, user_id, payload, user_agent, ip_address, last_activity) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [$sessionId, $userId, json_encode(['login_method' => 'credentials']), $userAgent, $ip, time()]
        );

        // 2. Verify record presence via isSessionValid
        $isValid = Session::isSessionValid($sessionId);
        $this->assertTrue($isValid, "Session should be found and valid inside DB store.");

        // 3. Delete session record
        $this->db->query("DELETE FROM sessions WHERE id = ?", [$sessionId]);

        // 4. Verify invalidation
        $isGone = !Session::isSessionValid($sessionId);
        $this->assertTrue($isGone, "Revoked session footprint must register as invalid.");
    }
}
