<?php
namespace App\Middleware;

use App\Core\Database;

use App\Core\MiddlewareInterface;

/**
 * IP-based Rate Limiting Middleware
 * Thwarts brute-force attacks and abuse on critical API and Auth endpoints
 */
class RateLimitMiddleware implements MiddlewareInterface {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function handle(callable $next): void {
        $ip = $this->getClientIp();
        // Identify unique endpoint (route mapping)
        $endpoint = $_SERVER['REQUEST_URI'] ?? '/';
        // Strip query string for grouping
        if (($pos = strpos($endpoint, '?')) !== false) {
            $endpoint = substr($endpoint, 0, $pos);
        }

        $now = time();
        $window = RATE_LIMIT_WINDOW; // 60 seconds
        $maxAttempts = RATE_LIMIT_MAX_ATTEMPTS; // 5

        // Fetch rate limit record
        $record = $this->db->fetch(
            "SELECT id, attempts, UNIX_TIMESTAMP(last_attempt) as last_attempt_time FROM rate_limits 
             WHERE ip_address = ? AND endpoint = ?",
            [$ip, $endpoint]
        );

        if ($record) {
            $elapsed = $now - (int)$record['last_attempt_time'];

            if ($elapsed > $window) {
                // Window expired: Reset attempts
                $this->db->query(
                    "UPDATE rate_limits SET attempts = 1, last_attempt = CURRENT_TIMESTAMP WHERE id = ?",
                    [$record['id']]
                );
            } else {
                if ((int)$record['attempts'] >= $maxAttempts) {
                    // Block access
                    http_response_code(429);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Too many requests. Please try again after ' . ($window - $elapsed) . ' seconds.',
                        'retry_after' => ($window - $elapsed)
                    ]);
                    
                    // Log rate limiting infraction securely to trace potential attackers
                    error_log("Rate limit exceeded for IP: {$ip} on endpoint: {$endpoint}");
                    exit;
                } else {
                    // Increment attempts
                    $this->db->query(
                        "UPDATE rate_limits SET attempts = attempts + 1 WHERE id = ?",
                        [$record['id']]
                    );
                }
            }
        } else {
            // Register new ip-endpoint track
            $this->db->query(
                "INSERT INTO rate_limits (ip_address, endpoint, attempts) VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE attempts = attempts + 1",
                [$ip, $endpoint]
            );
        }

        $next();
    }

    /**
     * Resolves client IP address, sanitizing headers to prevent IP spoofing
     */
    private function getClientIp(): string {
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->isValidIp($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Forwarded chain may contain multiple IPs: verify first element
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $firstIp = trim($ips[0]);
            if ($this->isValidIp($firstIp)) {
                return $firstIp;
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private function isValidIp(string $ip): bool {
        return (bool)filter_var($ip, FILTER_VALIDATE_IP);
    }
}
