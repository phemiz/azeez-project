<?php
namespace App\Services;

use App\Core\Database;

/**
 * Enterprise Audit Trail Logging Service
 * Records low-level database state transformations, tracking who, what, when, where,
 * IP, browser configurations, session IDs, and pre/post-operation values.
 */
class AuditService {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->selfHealDatabase();
    }

    /**
     * Ensures audit_trail table has the compliance logging columns (IP, User Agent, Session ID).
     */
    private function selfHealDatabase(): void {
        try {
            $this->db->query("ALTER TABLE audit_trail ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) DEFAULT NULL");
            $this->db->query("ALTER TABLE audit_trail ADD COLUMN IF NOT EXISTS user_agent VARCHAR(255) DEFAULT NULL");
            $this->db->query("ALTER TABLE audit_trail ADD COLUMN IF NOT EXISTS session_id VARCHAR(128) DEFAULT NULL");
        } catch (\Exception $e) {
            // Suppress error if columns already exist or database driver doesn't support IF NOT EXISTS alter syntaxes
        }
    }

    /**
     * Commits a state alteration audit log entry.
     */
    public function log(string $tableName, int $recordId, string $actionType, ?array $oldValues, ?array $newValues, ?int $performedBy = null): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $sessId = session_id() ?: 'N/A';

        $this->db->query(
            "INSERT INTO audit_trail (table_name, record_id, action_type, old_values, new_values, performed_by, ip_address, user_agent, session_id) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $tableName, 
                $recordId, 
                $actionType, 
                $oldValues ? json_encode($oldValues) : null, 
                $newValues ? json_encode($newValues) : null, 
                $performedBy, 
                $ip, 
                $ua, 
                $sessId
            ]
        );
    }

    /**
     * Compiles filtered logs history ledger for the SIEM console.
     */
    public function getAudits(array $filters, string $sortBy = 'created_at', string $sortOrder = 'DESC', int $limit = 10, int $offset = 0): array {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(a.table_name LIKE ? OR a.action_type LIKE ? OR u.username LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['action_type'])) {
            $where[] = "a.action_type = ?";
            $params[] = $filters['action_type'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = "a.created_at >= ?";
            $params[] = $filters['start_date'] . " 00:00:00";
        }
        if (!empty($filters['end_date'])) {
            $where[] = "a.created_at <= ?";
            $params[] = $filters['end_date'] . " 23:59:59";
        }

        $whereSql = "";
        if (!empty($where)) {
            $whereSql = "WHERE " . implode(" AND ", $where);
        }

        $allowedSorts = ['id', 'created_at', 'table_name', 'action_type'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        // Total count
        $countParams = $params;
        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM audit_trail a 
             LEFT JOIN users u ON a.performed_by = u.id 
             {$whereSql}",
            $countParams
        );

        // Fetch logs
        $querySql = "SELECT a.*, u.username as operator 
                     FROM audit_trail a 
                     LEFT JOIN users u ON a.performed_by = u.id 
                     {$whereSql} 
                     ORDER BY a.`{$sortBy}` {$sortOrder} 
                     LIMIT {$limit} OFFSET {$offset}";

        $rows = $this->db->fetchAll($querySql, $params);

        return [
            'total' => $total,
            'rows'  => $rows
        ];
    }
}
