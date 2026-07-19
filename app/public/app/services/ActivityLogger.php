<?php
namespace App\Services;

use App\Repositories\AuditLogRepository;

/**
 * Enterprise Activity Auditing Service
 * Tracks standard user operations (encryption, decryption, routing interactions)
 */
class ActivityLogger {
    private AuditLogRepository $auditRepo;

    public function __construct(AuditLogRepository $auditRepo) {
        $this->auditRepo = $auditRepo;
    }

    public function log(string $action, ?int $userId = null): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $this->auditRepo->logActivity($userId, $action, $ip, $ua);

        // Centralized file and browser parsing log
        $loggingService = new LoggingService();
        $loggingService->log($action, $userId);
    }
}
