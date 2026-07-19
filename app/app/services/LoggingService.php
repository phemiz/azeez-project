<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Session;

/**
 * Centralized Enterprise Logging Service
 * Coordinates dual-logging (MySQL 3NF Database & secure local logs file),
 * automatically parses user-agent headers for browser, OS, and device categories,
 * and tracks session identifiers to maintain transaction records.
 */
class LoggingService {
    private Database $db;
    private string $logFilePath;

    public function __construct() {
        $this->db = Database::getInstance();
        
        $logDir = dirname(dirname(__DIR__)) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $this->logFilePath = $logDir . '/security.log';
    }

    /**
     * Records an activity event in both MySQL and local secure log file.
     * 
     * @param string $action Event descriptor (e.g., 'login', 'decrypt')
     * @param int|null $userId Profile user ID associated with event
     * @param float $riskScore Threat score evaluated by AI engine
     * @param string $classification Threat classification (e.g. 'SQLi Attempt')
     * @param string $severity Event severity ('low', 'medium', 'high', 'critical')
     * @param string $details Additional event metadata
     */
    public function log(string $action, ?int $userId = null, float $riskScore = 0.0, string $classification = 'Normal', string $severity = 'low', string $details = ''): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $sessionId = session_id() ?: 'NO_SESSION';
        $timestamp = date('Y-m-d H:i:s');

        // Parse user agent details
        $os = $this->detectOS($ua);
        $browser = $this->detectBrowser($ua);
        $device = $this->detectDevice($ua);

        // 1. Log to MySQL activity_logs table
        try {
            $sql = "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            // Format details to append OS/Browser info
            $fullDetails = $details ?: "Transaction executed on {$os}/{$browser} ({$device})";

            $this->db->query($sql, [
                $userId,
                $action,
                $ip,
                $ua,
                $riskScore,
                $classification,
                $severity,
                $fullDetails
            ]);
        } catch (\Exception $e) {
            error_log("Database logging failed: " . $e->getMessage());
        }

        // 2. Route the file-based audit entry to our unified Logger framework
        try {
            $channel = \App\Core\Logger::CHANNEL_ACTIVITY;
            $actionLower = strtolower($action);
            if (strpos($actionLower, 'login') !== false || strpos($actionLower, 'logout') !== false || strpos($actionLower, 'otp') !== false || strpos($actionLower, 'register') !== false || strpos($actionLower, 'auth') !== false) {
                $channel = \App\Core\Logger::CHANNEL_AUTH;
            } elseif (strpos($actionLower, 'backup') !== false || strpos($actionLower, 'restore') !== false) {
                $channel = \App\Core\Logger::CHANNEL_BACKUP;
            } elseif (strpos($actionLower, 'encrypt') !== false || strpos($actionLower, 'decrypt') !== false || strpos($actionLower, 'payload') !== false || strpos($actionLower, 'api') !== false) {
                $channel = \App\Core\Logger::CHANNEL_API;
            } elseif (strpos($actionLower, 'security') !== false || strpos($actionLower, 'alert') !== false || strpos($actionLower, 'lock') !== false || strpos($actionLower, 'suspend') !== false || strpos($actionLower, 'threat') !== false) {
                $channel = \App\Core\Logger::CHANNEL_SECURITY;
            } elseif (strpos($actionLower, 'ai') !== false || strpos($actionLower, 'behavior') !== false || strpos($actionLower, 'risk') !== false) {
                $channel = \App\Core\Logger::CHANNEL_AI;
            } elseif (strpos($actionLower, 'error') !== false || strpos($actionLower, 'fail') !== false || strpos($actionLower, 'exception') !== false) {
                $channel = \App\Core\Logger::CHANNEL_ERROR;
            }

            // Map severity to PSR-3 log levels
            $level = \App\Core\Logger::INFO;
            $severityLower = strtolower($severity);
            if ($severityLower === 'critical') {
                $level = \App\Core\Logger::CRITICAL;
            } elseif ($severityLower === 'high') {
                $level = \App\Core\Logger::ERROR;
            } elseif ($severityLower === 'medium') {
                $level = \App\Core\Logger::WARNING;
            } elseif ($severityLower === 'low') {
                $level = \App\Core\Logger::INFO;
            }

            // Centralized Log execution
            $logger = \App\Core\Logger::getInstance();
            $logger->log($channel, $level, $action, [
                'user_id'               => $userId,
                'risk_score'            => $riskScore,
                'threat_classification' => $classification,
                'severity'              => $severity,
                'details'               => $details,
                'operating_system'      => $os,
                'browser'               => $browser,
                'device_category'       => $device
            ]);
        } catch (\Exception $e) {
            error_log("Logging Service Router Failure: " . $e->getMessage());
        }
    }

    /**
     * Evaluates Operating System from User Agent.
     */
    private function detectOS(string $ua): string {
        if (preg_match('/windows|win32/i', $ua)) return 'Windows';
        if (preg_match('/macintosh|mac os x/i', $ua)) return 'macOS';
        if (preg_match('/linux/i', $ua)) return 'Linux';
        if (preg_match('/android/i', $ua)) return 'Android';
        if (preg_match('/iphone|ipad|ipod/i', $ua)) return 'iOS';
        
        return 'Unknown OS';
    }

    /**
     * Evaluates Browser name from User Agent.
     */
    private function detectBrowser(string $ua): string {
        if (preg_match('/edge/i', $ua)) return 'Edge';
        if (preg_match('/chrome/i', $ua)) return 'Chrome';
        if (preg_match('/firefox/i', $ua)) return 'Firefox';
        if (preg_match('/safari/i', $ua)) return 'Safari';
        if (preg_match('/opera|opr/i', $ua)) return 'Opera';
        
        return 'Unknown Browser';
    }

    /**
     * Evaluates Device category from User Agent.
     */
    private function detectDevice(string $ua): string {
        if (preg_match('/mobile|phone|ipod/i', $ua)) return 'Mobile';
        if (preg_match('/tablet|ipad/i', $ua)) return 'Tablet';
        
        return 'Desktop';
    }
}
