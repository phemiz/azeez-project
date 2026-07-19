<?php
namespace Tests\Unit;

use Tests\Framework\BaseTest;
use App\Services\EncryptionService;

/**
 * Unit Test for EncryptionService cipher routines
 */
class EncryptionTest extends BaseTest {
    private EncryptionService $crypto;

    public function setUp(): void {
        $this->crypto = new EncryptionService();
    }

    public function testEncryptionDecryption(): void {
        $message = "Top Secret Core Satellite Signal: 433.0MHz";
        $passphrase = "GuardSecuritySecret_123";

        // Encrypt payload
        $result = $this->crypto->encrypt($message, $passphrase);

        $this->assertNotNull($result['ciphertext']);
        $this->assertNotNull($result['iv']);
        $this->assertNotNull($result['salt']);
        $this->assertNotNull($result['signature']);

        // Decrypt payload
        $decrypted = $this->crypto->decrypt(
            $result['ciphertext'],
            $result['iv'],
            $result['salt'],
            $result['signature'],
            $passphrase
        );

        $this->assertEquals($message, $decrypted, "Decrypted message must match original text.");
    }

    public function testTamperingFails(): void {
        $message = "Secure WAF credentials payload.";
        $passphrase = "SecretPass";

        $result = $this->crypto->encrypt($message, $passphrase);

        // Tamper ciphertext
        $tamperedCiphertext = base64_encode(base64_decode($result['ciphertext']) . 'tamper');

        $tampered = false;
        try {
            $this->crypto->decrypt(
                $tamperedCiphertext,
                $result['iv'],
                $result['salt'],
                $result['signature'],
                $passphrase
            );
        } catch (\Exception $e) {
            $tampered = (strpos($e->getMessage(), 'Cryptographic decryption failed') !== false);
        }

        $this->assertTrue($tampered, "Tampered ciphertext signature check must abort decryption execution.");
    }
}
