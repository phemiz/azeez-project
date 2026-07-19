<?php
namespace App\Services;

use App\Repositories\AuditLogRepository;

/**
 * Enterprise Security Logger Service
 * Manages WAF events, AI alarms, database change ledgers, and critical alerts.
 */
class SecurityLogger {
    private AuditLogRepository $auditRepo;

    public function __construct(AuditLogRepository $auditRepo) {
        $this->auditRepo = $auditRepo;
    }

    /**
     * Logs critical security threat event.
     */
    public function logAlert(string $severity, string $message, ?int $userId = null): void {
        $this->auditRepo->logSecurityAlert($userId, $severity, $message);

        // Centralized security alert audit
        $loggingService = new LoggingService();
        $loggingService->log('security_alert', $userId, 50.0, 'Security Alert', $severity, $message);
    }

    /**
     * Audits row-level database modifications.
     */
    public function logChange(string $table, int $recordId, string $actionType, ?array $oldData = null, ?array $newData = null, ?int $operatorId = null): void {
        $oldStr = $oldData ? json_encode($oldData) : null;
        $newStr = $newData ? json_encode($newData) : null;

        $this->auditRepo->logAuditTrail(
            $table,
            $recordId,
            $actionType,
            $oldStr,
            $newStr,
            $operatorId
        );

        // Centralized database modifications log
        $loggingService = new LoggingService();
        $loggingService->log("db_change_{$actionType}", $operatorId, 0.0, 'Database Audit', 'low', "Table: {$table}, Record ID: {$recordId}");
    }
}
