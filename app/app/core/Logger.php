<?php
namespace App\Core;

/**
 * Enterprise Reusable Logging Framework
 * Supports PSR-3 compliant levels, multiple target log channels (activity, security, error, etc.),
 * dynamic contextual fields extraction, and thread-safe automatic log rotation.
 */
class Logger {
    // Log Levels
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const NOTICE = 'NOTICE';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';
    public const ALERT = 'ALERT';
    public const EMERGENCY = 'EMERGENCY';

    // Channels
    public const CHANNEL_ERROR = 'error';
    public const CHANNEL_ACTIVITY = 'activity';
    public const CHANNEL_SECURITY = 'security';
    public const CHANNEL_AI = 'ai';
    public const CHANNEL_BACKUP = 'backup';
    public const CHANNEL_AUTH = 'auth';
    public const CHANNEL_API = 'api';

    private string $logDir;
    private int $maxFileSize; // in bytes (e.g., 5MB)
    private int $maxBackupFiles;

    private static ?Logger $instance = null;

    private function __construct() {
        $this->logDir = dirname(dirname(__DIR__)) . '/logs';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        $this->maxFileSize = 5 * 1024 * 1024; // 5 MB default
        $this->maxBackupFiles = 5;
    }

    public static function getInstance(): Logger {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Override limits for testing purposes
     */
    public function setLimits(int $maxFileSize, int $maxBackupFiles): void {
        $this->maxFileSize = $maxFileSize;
        $this->maxBackupFiles = $maxBackupFiles;
    }

    /**
     * Core logging function
     */
    public function log(string $channel, string $level, string $message, array $context = []): void {
        $timestamp = date('Y-m-d H:i:s');
        $sessionId = session_id() ?: 'NO_SESSION';
        
        // Retrieve User ID if logged in
        $userId = 'ANONYMOUS';
        if (isset($_SESSION['user']['id'])) {
            $userId = $_SESSION['user']['id'];
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Prepare context data
        $contextStr = '';
        if (!empty($context)) {
            $contextStr = ' | Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // Format: [2026-07-15 12:15:00] [CHANNEL] [LEVEL] [IP] [USER:123] [SESSION:xyz] Message | Context: {...}
        $logLine = sprintf(
            "[%s] [%s] [%s] [%s] [USER:%s] [SESSION:%s] %s%s%s",
            $timestamp,
            strtoupper($channel),
            strtoupper($level),
            $ip,
            $userId,
            $sessionId,
            $message,
            $contextStr,
            PHP_EOL
        );

        $filePath = $this->logDir . '/' . strtolower($channel) . '.log';

        // Perform thread-safe write with file locking and automatic rotation
        $this->writeAndRotate($filePath, $logLine);
    }

    /**
     * Thread-safe write and log rotation
     */
    private function writeAndRotate(string $filePath, string $logLine): void {
        try {
            // Write log line to file with lock
            file_put_contents($filePath, $logLine, FILE_APPEND | LOCK_EX);

            // Clear PHP stat cache to get fresh filesize
            clearstatcache(true, $filePath);

            // Check if rotation is needed
            if (file_exists($filePath) && filesize($filePath) >= $this->maxFileSize) {
                $this->rotate($filePath);
            }
        } catch (\Exception $e) {
            error_log("Logging Framework Failure: " . $e->getMessage());
        }
    }

    /**
     * Rotates log files: file.log -> file.log.1 -> file.log.2
     */
    private function rotate(string $filePath): void {
        // Shift existing backups
        for ($i = $this->maxBackupFiles - 1; $i >= 1; $i--) {
            $oldFile = $filePath . '.' . $i;
            $newFile = $filePath . '.' . ($i + 1);
            if (file_exists($oldFile)) {
                if (file_exists($newFile)) {
                    unlink($newFile);
                }
                rename($oldFile, $newFile);
            }
        }

        // Move current file to backup .1
        if (file_exists($filePath)) {
            rename($filePath, $filePath . '.1');
        }
    }

    // Short-hand helper methods matching PSR-3 interfaces
    public function debug(string $channel, string $message, array $context = []): void {
        $this->log($channel, self::DEBUG, $message, $context);
    }

    public function info(string $channel, string $message, array $context = []): void {
        $this->log($channel, self::INFO, $message, $context);
    }

    public function notice(string $channel, string $message, array $context = []): void {
        $this->log($channel, self::NOTICE, $message, $context);
    }

    public function warning(string $channel, string $message, array $context = []): void {
        $this->log($channel, self::WARNING, $message, $context);
    }

    public function error(string $channel, string $message, array $context = []): void {
        $this->log($channel, self::ERROR, $message, $context);
    }

    public function critical(string $channel, string $message, array $context = []): void {
        $this->log($channel, self::CRITICAL, $message, $context);
    }

    public function alert(string $channel, string $message, array $context = []): void {
        $this->log($channel, self::ALERT, $message, $context);
    }

    public function emergency(string $channel, string $message, array $context = []): void {
        $this->log($channel, self::EMERGENCY, $message, $context);
    }
}
