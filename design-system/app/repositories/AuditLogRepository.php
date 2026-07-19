<?php
namespace App\Repositories;

use App\Core\Repository;

/**
 * Repository managing logs, alerts, and audit ledgers
 */
class AuditLogRepository extends Repository {
    protected string $table = 'activity_logs';

    /**
     * Adds generic activity record.
     */
    public function logActivity(?int $userId, string $action, string $ip, string $ua): void {
        $this->create([
            'user_id'    => $userId,
            'action'     => $action,
            'ip_address' => $ip,
            'user_agent' => $ua
        ]);
    }

    /**
     * Adds critical security alarm event.
     */
    public function logSecurityAlert(?int $userId, string $severity, string $message, string $status = 'open'): void {
        $qb = new \App\Core\QueryBuilder();
        $qb->table('security_alerts')->insert([
            'user_id'    => $userId,
            'severity'   => $severity,
            'message'    => $message,
            'status'     => $status
        ]);
    }

    /**
     * Adds database modification trail event.
     */
    public function logAuditTrail(string $tableName, int $recordId, string $actionType, ?string $oldValues, ?string $newValues, ?int $performedBy): void {
        $qb = new \App\Core\QueryBuilder();
        $qb->table('audit_trail')->insert([
            'table_name'   => $tableName,
            'record_id'    => $recordId,
            'action_type'  => $actionType,
            'old_values'   => $oldValues,
            'new_values'   => $newValues,
            'performed_by' => $performedBy
        ]);
    }

    /**
     * Fetches recent global logs.
     */
    public function getRecentLogs(int $limit = 50): array {
        return $this->query()
            ->select(['activity_logs.*', 'users.username'])
            ->leftJoin('users', 'activity_logs.user_id', '=', 'users.id')
            ->orderBy('activity_logs.created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Fetches user-specific history.
     */
    public function getUserLogs(int $userId, int $limit = 10): array {
        return $this->query()
            ->where('user_id', '=', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
}
