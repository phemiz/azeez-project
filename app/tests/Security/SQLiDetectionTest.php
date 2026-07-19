<?php
namespace Tests\Security;

use Tests\Framework\BaseTest;
use App\Services\AIEngine;

/**
 * Security Test validating AI Engine Heuristics rules for SQL Injection (SQLi)
 */
class SQLiDetectionTest extends BaseTest {
    private AIEngine $ai;

    public function setUp(): void {
        $this->ai = new AIEngine();
    }

    public function testSQLiDetection(): void {
        $ip = '127.0.0.1';
        $ua = 'Mozilla/5.0 TerminalClient';
        
        // Attack payload simulation
        $sqliPayload = ['message' => "SELECT * FROM users WHERE username = 'admin' OR 1=1 --"];
        
        $report = $this->ai->analyzeSecurityEvent(null, 'encrypt_message', $ip, $ua, $sqliPayload);

        $this->assertTrue($report['risk_score'] >= 60, "SQL Injection payload must trigger high risk score.");
        $this->assertStringContains("SQL Injection Attempt", $report['threat_classification']);
    }
}
