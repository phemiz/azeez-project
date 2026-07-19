<?php
namespace App\Core;

/**
 * Enterprise Secure Cookie Manager
 * Sets, retrieves, and signs cookies with HMAC validation to prevent client-side tampering.
 */
class Cookie {
    private static string $key = APP_SECRET_KEY;

    /**
     * Sets a secure signed cookie.
     */
    public static function set(string $name, string $value, int $expiry = 0, string $path = '/', ?string $domain = null, bool $secure = SESSION_SECURE, bool $httpOnly = true, string $sameSite = 'Strict'): bool {
        // Sign cookie value using HMAC to prevent tampering
        $signature = hash_hmac('sha256', $value, self::$key);
        $signedValue = base64_encode(json_encode([
            'value' => $value,
            'sig'   => $signature
        ]));

        $options = [
            'expires'  => $expiry > 0 ? time() + $expiry : 0,
            'path'     => $path,
            'domain'   => $domain ?? '',
            'secure'   => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite
        ];

        return setcookie($name, $signedValue, $options);
    }

    /**
     * Retrieves a secure cookie value after verifying its signature.
     */
    public static function get(string $name, $default = null): ?string {
        if (!isset($_COOKIE[$name])) {
            return $default;
        }

        $decoded = base64_decode($_COOKIE[$name], true);
        if ($decoded === false) {
            return $default;
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload) || !isset($payload['value'], $payload['sig'])) {
            return $default;
        }

        // Verify HMAC signature (constant-time check)
        $expectedSignature = hash_hmac('sha256', $payload['value'], self::$key);
        if (!hash_equals($expectedSignature, $payload['sig'])) {
            error_log("Cookie tampering detected on key: " . $name);
            return $default;
        }

        return $payload['value'];
    }

    /**
     * Deletes a cookie.
     */
    public static function delete(string $name, string $path = '/', ?string $domain = null): bool {
        if (isset($_COOKIE[$name])) {
            unset($_COOKIE[$name]);
            return setcookie($name, '', [
                'expires' => time() - 3600,
                'path'    => $path,
                'domain'  => $domain ?? '',
                'secure'  => SESSION_SECURE,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        return false;
    }
}
