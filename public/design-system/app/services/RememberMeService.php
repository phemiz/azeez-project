<?php
namespace App\Services;

use App\Core\Cookie;
use App\Repositories\UserRepository;

/**
 * Remember Me Automatic Login Service
 * Manages long-term encrypted signed cookies to automatically log in returning nodes
 */
class RememberMeService {
    private EncryptionService $crypto;
    private UserRepository $userRepo;
    private string $cookieName = 'gsm_remember_node';
    private int $cookieLifetime = 2592000; // 30 days (in seconds)

    public function __construct(EncryptionService $crypto, UserRepository $userRepo) {
        $this->crypto = $crypto;
        $this->userRepo = $userRepo;
    }

    /**
     * Issue a secure remember me token cookie.
     */
    public function issueToken(int $userId): void {
        $expiry = time() + $this->cookieLifetime;
        
        // Encrypt the user ID and expiration timestamp to prevent disclosure
        $rawPayload = $userId . ':' . $expiry;
        $encrypted = $this->crypto->encrypt($rawPayload);
        
        $cookieValue = base64_encode(json_encode($encrypted));

        // Save signed secure cookie
        Cookie::set($this->cookieName, $cookieValue, $this->cookieLifetime);
    }

    /**
     * Attempts to automatically authenticate the client using the cookie token.
     * 
     * @return array|null Returns user data array on success, null on failure.
     */
    public function autoLogin(): ?array {
        $cookieValue = Cookie::get($this->cookieName);
        if (!$cookieValue) {
            return null;
        }

        $encrypted = json_decode(base64_decode($cookieValue), true);
        if (!is_array($encrypted) || !isset($encrypted['ciphertext'], $encrypted['iv'], $encrypted['salt'], $encrypted['signature'])) {
            return null;
        }

        try {
            // Decrypt payload
            $decrypted = $this->crypto->decrypt(
                $encrypted['ciphertext'],
                $encrypted['iv'],
                $encrypted['salt'],
                $encrypted['signature']
            );

            list($userId, $expiry) = explode(':', $decrypted, 2);
            $userId = (int)$userId;
            $expiry = (int)$expiry;

            // Check expiration
            if ($expiry < time()) {
                $this->clearToken();
                return null;
            }

            // Fetch user
            $user = $this->userRepo->find($userId);
            if ($user && $user['status'] === 'active') {
                return $user;
            }
        } catch (\Exception $e) {
            error_log("Auto-login token decryption failure: " . $e->getMessage());
            $this->clearToken();
        }

        return null;
    }

    /**
     * Deletes the remember me cookie token.
     */
    public function clearToken(): void {
        Cookie::delete($this->cookieName);
    }
}
