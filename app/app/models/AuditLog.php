<?php
namespace App\Models;

use App\Core\Model;
use App\Repositories\AuditLogRepository;

/**
 * Audit Log Model
 * Proxies operations to AuditLogRepository using Repository Pattern
 */
class AuditLog extends Model {
    private AuditLogRepository $repo;

    public function __construct() {
        parent::__construct();
        $this->repo = new AuditLogRepository();
    }

    /**
     * Records standard user panel event
     */
    public function logActivity(?int $userId, string $action, string $ip, string $ua): void {
        $this->repo->logActivity($userId, $action, $ip, $ua);
    }

    /**
     * Records security threat alarm
     */
    public function logSecurityAlert(?int $userId, string $severity, string $message, string $status = 'open'): void {
        $this->repo->logSecurityAlert($userId, $severity, $message, $status);
    }

    /**
     * Records database changes for compliance ledger
     */
    public function logAuditTrail(string $tableName, int $recordId, string $actionType, ?string $oldValues, ?string $newValues, ?int $performedBy): void {
        $this->repo->logAuditTrail($tableName, $recordId, $actionType, $oldValues, $newValues, $performedBy);
    }

    /**
     * Fetches recent activity logs
     */
    public function getRecentLogs(int $limit = 50): array {
        return $this->repo->getRecentLogs($limit);
    }

    /**
     * Fetches user-specific history
     */
    public function getUserLogs(int $userId, int $limit = 10): array {
        return $this->repo->getUserLogs($userId, $limit);
    }
}
