<?php
namespace Tests\Performance;

use Tests\Framework\BaseTest;
use App\Services\EncryptionService;

/**
 * Performance Test for Cryptographic Throughput Operations
 */
class CryptoThroughputTest extends BaseTest {
    private EncryptionService $crypto;

    public function setUp(): void {
        $this->crypto = new EncryptionService();
    }

    public function testCryptoThroughput(): void {
        $message = "Heuristics radar tracking coordinates: LAT:45.3 N, LON:122.6 W";
        $passphrase = "HighSpeedPassphrase_XYZ";

        $iterations = 50;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $enc = $this->crypto->encrypt($message, $passphrase);
            $dec = $this->crypto->decrypt($enc['ciphertext'], $enc['iv'], $enc['salt'], $enc['signature'], $passphrase);
            $this->assertEquals($message, $dec);
        }

        $duration = microtime(true) - $startTime;
        $averageMs = ($duration / $iterations) * 1000;

        // Assert average execution time is under 150ms per encrypt-then-mac roundtrip
        $this->assertTrue($averageMs < 150.0, "Average roundtrip should be under 150ms (Actual: {$averageMs}ms).");
    }
}
