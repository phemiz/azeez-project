<?php
namespace App\Repositories;

use App\Core\Database;

/**
 * Enterprise SIEM Reports Repository
 * Compiles database-level ledger records for users, security alarms, threat detections,
 * logins, encryptions, OTP codes, AI reports, activity trials, and audits.
 */
class ReportsRepository {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Retrieves compiled logs and metadata relative to report selection parameters.
     */
    public function getReportData(string $type, array $filters, string $sortBy = 'created_at', string $sortOrder = 'DESC', int $limit = 10, int $offset = 0): array {
        $where = [];
        $params = [];

        // Apply Common Date Filters
        if (!empty($filters['start_date'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['start_date'] . " 00:00:00";
        }
        if (!empty($filters['end_date'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['end_date'] . " 23:59:59";
        }

        // Setup base query depending on Report Type
        switch ($type) {
            case 'users':
                $table = "users";
                $fields = "id, username, email, phone, status, created_at";
                if (!empty($filters['search'])) {
                    $where[] = "(username LIKE ? OR email LIKE ?)";
                    $params[] = "%{$filters['search']}%";
                    $params[] = "%{$filters['search']}%";
                }
                break;

            case 'security':
                $table = "security_alerts";
                $fields = "id, severity, status, message AS triggered_rule, created_at";
                if (!empty($filters['search'])) {
                    $where[] = "(message LIKE ? OR severity LIKE ?)";
                    $params[] = "%{$filters['search']}%";
                    $params[] = "%{$filters['search']}%";
                }
                break;

            case 'threats':
                $table = "activity_logs";
                $fields = "id, action, ip_address, risk_score, threat_classification, severity, created_at";
                $where[] = "risk_score > 0";
                if (!empty($filters['search'])) {
                    $where[] = "(action LIKE ? OR threat_classification LIKE ?)";
                    $params[] = "%{$filters['search']}%";
                    $params[] = "%{$filters['search']}%";
                }
                break;

            case 'logins':
                $table = "activity_logs";
                $fields = "id, action, ip_address, user_agent, created_at";
                $where[] = "action LIKE 'login_success%'";
                if (!empty($filters['search'])) {
                    $where[] = "(ip_address LIKE ? OR user_agent LIKE ?)";
                    $params[] = "%{$filters['search']}%";
                    $params[] = "%{$filters['search']}%";
                }
                break;

            case 'encryptions':
                $table = "encrypted_messages";
                $fields = "id, recipient, 'AES-256-CBC' AS algorithm, 'Low' AS risk_grade, created_at";
                if (!empty($filters['search'])) {
                    $where[] = "recipient LIKE ?";
                    $params[] = "%{$filters['search']}%";
                }
                break;

            case 'otp':
                $table = "otp_codes";
                $fields = "id, ip_address, status, expires_at, created_at";
                if (!empty($filters['search'])) {
                    $where[] = "(ip_address LIKE ? OR status LIKE ?)";
                    $params[] = "%{$filters['search']}%";
                    $params[] = "%{$filters['search']}%";
                }
                break;

            case 'ai':
                $table = "activity_logs";
                $fields = "id, action, threat_classification, risk_score, threat_details, created_at";
                $where[] = "risk_score >= 30";
                if (!empty($filters['search'])) {
                    $where[] = "(threat_classification LIKE ? OR threat_details LIKE ?)";
                    $params[] = "%{$filters['search']}%";
                    $params[] = "%{$filters['search']}%";
                }
                break;

            case 'activity':
            case 'audit':
                $table = "activity_logs";
                $fields = "id, action, ip_address, risk_score, created_at";
                if (!empty($filters['search'])) {
                    $where[] = "(action LIKE ? OR ip_address LIKE ?)";
                    $params[] = "%{$filters['search']}%";
                    $params[] = "%{$filters['search']}%";
                }
                break;

            case 'system':
                // System Reports queries system setting states and backup history counts
                $table = "activity_logs";
                $fields = "id, action, ip_address, created_at";
                $where[] = "action LIKE 'db_%'";
                if (!empty($filters['search'])) {
                    $where[] = "action LIKE ?";
                    $params[] = "%{$filters['search']}%";
                }
                break;

            default:
                throw new \InvalidArgumentException("Invalid reports category selected.");
        }

        // Build SQL
        $whereSql = "";
        if (!empty($where)) {
            $whereSql = "WHERE " . implode(" AND ", $where);
        }

        // Validate Sorting inputs to prevent injection queries
        $allowedSorts = ['id', 'created_at', 'risk_score', 'username', 'recipient'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        // Count total rows
        $countParams = $params;
        $total = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM `{$table}` {$whereSql}",
            $countParams
        );

        // Fetch rows
        $querySql = "SELECT {$fields} FROM `{$table}` 
                     {$whereSql} 
                     ORDER BY `{$sortBy}` {$sortOrder} 
                     LIMIT {$limit} OFFSET {$offset}";

        $rows = $this->db->fetchAll($querySql, $params);

        return [
            'total' => $total,
            'rows'  => $rows
        ];
    }
}
