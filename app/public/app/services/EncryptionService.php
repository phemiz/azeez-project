<?php
namespace App\Services;

/**
 * Enterprise Cryptographic Service using AES-256-CBC and PBKDF2
 * Enforces Encrypt-then-MAC paradigm for ciphertext integrity.
 */
class EncryptionService {
    private string $cipher;
    private string $masterKey;

    public function __construct() {
        $this->cipher = ENCRYPTION_CIPHER; // AES-256-CBC
        $this->masterKey = APP_SECRET_KEY;
    }

    /**
     * Encrypts plaintext using AES-256-CBC and PBKDF2 key derivation.
     * 
     * @param string $plaintext Data to encrypt.
     * @param string $passphrase Optional user passphrase to mix with the master key.
     * @return array Array containing ciphertext, iv, salt, and hmac signature (hex encoded).
     */
    public function encrypt(string $plaintext, string $passphrase = ''): array {
        try {
            // 1. Generate secure random salt and IV
            $salt = random_bytes(16);
            $ivLength = openssl_cipher_iv_length($this->cipher);
            $iv = random_bytes($ivLength);

            // 2. Derive encryption key using PBKDF2
            $derivedKey = hash_pbkdf2('sha256', $this->masterKey . $passphrase, $salt, 10000, 32, true);

            // 3. Encrypt data
            $ciphertext = openssl_encrypt($plaintext, $this->cipher, $derivedKey, OPENSSL_RAW_DATA, $iv);
            if ($ciphertext === false) {
                throw new \Exception("Encryption failure.");
            }

            // 4. Generate HMAC-SHA256 signature to guarantee ciphertext integrity (Encrypt-then-MAC)
            // HMAC is computed over (IV + Salt + Ciphertext)
            $payload = $iv . $salt . $ciphertext;
            $hmac = hash_hmac('sha256', $payload, $derivedKey, true);

            return [
                'ciphertext' => base64_encode($ciphertext),
                'iv'         => base64_encode($iv),
                'salt'       => base64_encode($salt),
                'signature'  => base64_encode($hmac)
            ];
        } catch (\Exception $e) {
            error_log("Encryption error: " . $e->getMessage());
            throw new \RuntimeException("Cryptographic operation failed.");
        }
    }

    /**
     * Decrypts ciphertext after verifying signature.
     * 
     * @param string $ciphertext Base64 encoded ciphertext.
     * @param string $iv Base64 encoded initialization vector.
     * @param string $salt Base64 encoded key-derivation salt.
     * @param string $signature Base64 encoded HMAC signature.
     * @param string $passphrase Optional user passphrase used during encryption.
     * @return string Plaintext.
     */
    public function decrypt(string $ciphertext, string $iv, string $salt, string $signature, string $passphrase = ''): string {
        try {
            $rawCiphertext = base64_decode($ciphertext);
            $rawIv = base64_decode($iv);
            $rawSalt = base64_decode($salt);
            $rawSignature = base64_decode($signature);

            // 1. Re-derive the key
            $derivedKey = hash_pbkdf2('sha256', $this->masterKey . $passphrase, $rawSalt, 10000, 32, true);

            // 2. Re-compute HMAC and perform timing-attack-safe comparison
            $payload = $rawIv . $rawSalt . $rawCiphertext;
            $expectedSignature = hash_hmac('sha256', $payload, $derivedKey, true);

            if (!hash_equals($expectedSignature, $rawSignature)) {
                throw new \SecurityException("Ciphertext integrity verification failed (Signature mismatch).");
            }

            // 3. Decrypt data
            $plaintext = openssl_decrypt($rawCiphertext, $this->cipher, $derivedKey, OPENSSL_RAW_DATA, $rawIv);
            if ($plaintext === false) {
                throw new \Exception("Decryption failure.");
            }

            return $plaintext;
        } catch (\Exception $e) {
            error_log("Decryption error: " . $e->getMessage());
            throw new \RuntimeException("Cryptographic decryption failed.");
        }
    }
}

// Custom exception classes for security logging
class SecurityException extends \Exception {}
