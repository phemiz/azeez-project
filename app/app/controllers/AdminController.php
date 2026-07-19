<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

/**
 * Controller managing Admin Operations: Auditing, User management, Backup & Restoration, and Threat Analytics
 */
class AdminController extends Controller {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function index(): void {
        $user = Session::get('user');

        // Fetch all user accounts
        $users = $this->db->fetchAll("SELECT u.id, u.username, u.email, CASE WHEN a.id IS NOT NULL THEN 'admin' ELSE 'user' END as role, u.status, u.created_at FROM users u LEFT JOIN admins a ON u.id = a.user_id ORDER BY u.created_at DESC");

        // Fetch system audit logs (all users)
        $logs = $this->db->fetchAll(
            "SELECT l.*, u.username FROM activity_logs l 
             LEFT JOIN users u ON l.user_id = u.id 
             ORDER BY l.created_at DESC LIMIT 50"
        );

        // Fetch Backup lists from directory
        $backupDir = dirname(dirname(__DIR__)) . '/backups/';
        $backups = [];
        if (is_dir($backupDir)) {
            $files = scandir($backupDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $filePath = $backupDir . $file;
                    $backups[] = [
                        'filename'   => $file,
                        'size'       => $this->formatBytes(filesize($filePath)),
                        'created_at' => date('Y-m-d H:i:s', filemtime($filePath))
                    ];
                }
            }
        }
        // Sort backups by creation date desc
        usort($backups, function($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        // Compute administrative analytics metrics
        $metrics = [
            'total_users'     => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM users"),
            'total_admins'    => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM admins"),
            'total_threats'   => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM risk_scores WHERE score > 0"),
            'critical_alerts' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM security_alerts WHERE severity = 'critical'"),
            'encrypted_count' => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM encrypted_messages")
        ];

        // Fetch recent logins
        $recentLogins = $this->db->fetchAll(
            "SELECT l.*, u.username FROM activity_logs l 
             INNER JOIN users u ON l.user_id = u.id 
             WHERE l.action LIKE 'login_success%' 
             ORDER BY l.created_at DESC LIMIT 5"
        );

        // Fetch recent registrations
        $recentRegistrations = $this->db->fetchAll(
            "SELECT username, email, created_at FROM users 
             ORDER BY created_at DESC LIMIT 5"
        );

        // Evaluate system health metrics
        $systemHealth = [
            'database' => 'ONLINE',
            'logs_directory' => is_writable(dirname(dirname(__DIR__)) . '/logs') ? 'WRITABLE' : 'UNWRITABLE',
            'backups_directory' => is_writable(dirname(dirname(__DIR__)) . '/backups') ? 'WRITABLE' : 'UNWRITABLE'
        ];

        // Fetch latest anomalous AI reports
        $latestAiReports = $this->db->fetchAll(
            "SELECT l.*, u.username FROM activity_logs l 
             LEFT JOIN users u ON l.user_id = u.id 
             WHERE l.risk_score >= 30 
             ORDER BY l.created_at DESC LIMIT 5"
        );

        // Threat classification distributions (for Chart.js rendering)
        $threatStats = $this->db->fetchAll(
            "SELECT threat_classification, COUNT(*) as count 
             FROM activity_logs 
             WHERE threat_classification != 'Normal' 
             GROUP BY threat_classification"
        );

        $this->view('admin/admin', [
            'title'               => 'Enterprise Security Administrator Control',
            'user'                => $user,
            'users'               => $users,
            'logs'                => $logs,
            'backups'             => $backups,
            'metrics'             => $metrics,
            'threatStats'         => $threatStats,
            'recentLogins'        => $recentLogins,
            'recentRegistrations' => $recentRegistrations,
            'systemHealth'        => $systemHealth,
            'latestAiReports'     => $latestAiReports
        ]);
    }

    public function threatsDashboard(): void {
        $user = Session::get('user');

        $totalThreats = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM activity_logs WHERE risk_score > 0");
        $criticalThreats = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM activity_logs WHERE risk_score >= 70");
        $mediumThreats = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM activity_logs WHERE risk_score >= 30 AND risk_score < 70");

        $classificationStats = $this->db->fetchAll(
            "SELECT threat_classification, COUNT(*) as count 
             FROM activity_logs 
             WHERE threat_classification != 'Normal' 
             GROUP BY threat_classification"
        );

        $recentThreatEvents = $this->db->fetchAll(
            "SELECT l.*, u.username FROM activity_logs l 
             LEFT JOIN users u ON l.user_id = u.id 
             WHERE l.risk_score > 0 
             ORDER BY l.created_at DESC LIMIT 20"
        );

        $dailyThreats = $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM activity_logs 
             WHERE risk_score > 0 AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) 
             GROUP BY DATE(created_at) 
             ORDER BY DATE(created_at) ASC"
        );

        $highRiskUsers = $this->db->fetchAll(
            "SELECT u.id, u.username, u.email, MAX(l.risk_score) as peak_risk, COUNT(l.id) as threat_count 
             FROM users u 
             INNER JOIN activity_logs l ON u.id = l.user_id 
             WHERE l.risk_score > 0 
             GROUP BY u.id 
             ORDER BY peak_risk DESC, threat_count DESC 
             LIMIT 5"
        );

        $globalScore = 100 - ($criticalThreats * 10) - ($mediumThreats * 3);
        $globalScore = max(40, min(100, $globalScore));

        $this->view('admin/threats', [
            'title'               => 'Enterprise AI Threat Intel Center',
            'user'                => $user,
            'totalThreats'        => $totalThreats,
            'criticalThreats'     => $criticalThreats,
            'mediumThreats'       => $mediumThreats,
            'classificationStats' => $classificationStats,
            'recentThreatEvents'  => $recentThreatEvents,
            'dailyThreats'        => $dailyThreats,
            'highRiskUsers'       => $highRiskUsers,
            'globalScore'         => $globalScore
        ]);
    }

    public function behaviorDashboard(): void {
        $user = Session::get('user');

        $users = $this->db->fetchAll("SELECT id, username FROM users ORDER BY username ASC");
        
        $targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)$user['id'];
        $targetUser = $this->db->fetch("SELECT id, username, email FROM users WHERE id = ?", [$targetUserId]);
        
        if (!$targetUser) {
            $this->redirect(APP_URL . '/admin');
        }

        $behaviorEngine = new \App\Services\BehaviorEngine();
        $behaviorProfile = $behaviorEngine->profileOperator($targetUserId);

        $this->view('admin/behavior', [
            'title'           => 'Operator Behavior Analytics',
            'user'            => $user,
            'users'           => $users,
            'targetUser'      => $targetUser,
            'behaviorProfile' => $behaviorProfile
        ]);
    }

    /**
     * API to toggle suspension of a user account
     */
    public function suspendUser(): void {
        $targetId = $this->getPost('user_id');
        $action = $this->getPost('status_action'); // 'suspend' or 'activate'

        if (empty($targetId) || empty($action)) {
            $this->json(['status' => 'error', 'message' => 'User ID and action parameters are required.'], 400);
        }

        $admin = Session::get('user');
        if ((int)$targetId === (int)$admin['id']) {
            $this->json(['status' => 'error', 'message' => 'Self-suspension is denied.'], 400);
        }

        $newStatus = ($action === 'suspend') ? 'suspended' : 'active';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Update DB
        $this->db->query("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $targetId]);

        // Audit log action
        $logMsg = "Administrator " . $admin['username'] . " changed status of user ID: " . $targetId . " to " . $newStatus;
        $this->db->query(
            "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
             VALUES (?, 'user_status_override', ?, ?, 0, 'Normal', 'low', ?)",
            [$admin['id'], $ip, $ua, $logMsg]
        );

        $this->json(['status' => 'success', 'message' => 'User account updated successfully.']);
    }

    /**
     * Generates a structural and data SQL backup dump file.
     */
    public function backup(): void {
        $admin = Session::get('user');
        $backupDir = dirname(dirname(__DIR__)) . '/backups/';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $tables = ['users', 'admins', 'otp_codes', 'encrypted_messages', 'activity_logs', 'security_alerts', 'sessions', 'password_resets', 'login_attempts', 'threat_reports', 'ai_recommendations', 'risk_scores', 'behavior_profiles', 'system_settings', 'backup_history', 'audit_trail'];
        $sqlDump = "-- GSM Cyber Security System SQL Dump\n";
        $sqlDump .= "-- Generated by admin: " . $admin['username'] . "\n";
        $sqlDump .= "-- Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Drop & Create table script
            $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $createTable = $this->db->fetch("SHOW CREATE TABLE `{$table}`");
            $sqlDump .= $createTable['Create Table'] . ";\n\n";

            // Insert records script
            $rows = $this->db->fetchAll("SELECT * FROM `{$table}`");
            if (!empty($rows)) {
                $sqlDump .= "INSERT INTO `{$table}` VALUES \n";
                $inserts = [];
                foreach ($rows as $row) {
                    $values = array_map(function($val) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        // Secure escaping for SQL inject strings
                        return $this->db->getConnection()->quote($val);
                    }, $row);
                    $inserts[] = "(" . implode(', ', $values) . ")";
                }
                $sqlDump .= implode(",\n", $inserts) . ";\n\n";
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $filename = 'backup_' . date('Ymd_His') . '_' . uniqid() . '.sql';
        $filePath = $backupDir . $filename;

        if (file_put_contents($filePath, $sqlDump) !== false) {
            // Log backup event in audit trail
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $this->db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'db_backup', ?, ?, 0, 'Normal', 'low', ?)",
                [$admin['id'], $ip, $ua, "Database backup file created: " . $filename]
            );

            $this->json(['status' => 'success', 'message' => 'System backup executed successfully: ' . $filename]);
        } else {
            $this->json(['status' => 'error', 'message' => 'File writing failure.'], 500);
        }
    }

    /**
     * Restores database from a selected SQL backup file
     */
    public function restore(): void {
        $filename = $this->getPost('filename');
        if (empty($filename)) {
            $this->json(['status' => 'error', 'message' => 'Backup filename parameter is required.'], 400);
        }

        // Mitigate Directory Traversal attacks
        $filename = basename($filename);
        $backupFile = dirname(dirname(__DIR__)) . '/backups/' . $filename;

        if (!file_exists($backupFile)) {
            $this->json(['status' => 'error', 'message' => 'Target backup file not found.'], 404);
        }

        $sqlContent = file_get_contents($backupFile);
        $admin = Session::get('user');

        try {
            $pdo = $this->db->getConnection();
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true); // Temporarily allow multi-query execution
            $pdo->exec($sqlContent);
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

            // Log restoration audit event
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $this->db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'db_restore', ?, ?, 0, 'Normal', 'low', ?)",
                [$admin['id'], $ip, $ua, "Database restored from backup file: " . $filename]
            );

            $this->json(['status' => 'success', 'message' => 'System restoration completed. Please reload.']);
        } catch (\PDOException $e) {
            $this->json(['status' => 'error', 'message' => 'Restoration database execution failure: ' . $e->getMessage()], 500);
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function usersDashboard(): void {
        $user = Session::get('user');

        $search = $_GET['search'] ?? '';
        $roleFilter = $_GET['role'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $params = [];
        $where = [];

        if (!empty($search)) {
            $where[] = "(u.username LIKE ? OR u.email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($roleFilter)) {
            if ($roleFilter === 'admin') {
                $where[] = "a.id IS NOT NULL";
            } else {
                $where[] = "a.id IS NULL";
            }
        }

        if (!empty($statusFilter)) {
            $where[] = "u.status = ?";
            $params[] = $statusFilter;
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = "WHERE " . implode(" AND ", $where);
        }

        // Count total matching users for pagination
        $countParams = $params;
        $totalUsers = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM users u 
             LEFT JOIN admins a ON u.id = a.user_id 
             {$whereSql}",
            $countParams
        );

        $totalPages = ceil($totalUsers / $limit);

        // Fetch users
        $querySql = "SELECT u.id, u.username, u.email, u.phone, 
                            CASE WHEN a.id IS NOT NULL THEN 'admin' ELSE 'user' END as role, 
                            u.status, u.created_at 
                     FROM users u 
                     LEFT JOIN admins a ON u.id = a.user_id 
                     {$whereSql} 
                     ORDER BY u.created_at DESC 
                     LIMIT {$limit} OFFSET {$offset}";

        $users = $this->db->fetchAll($querySql, $params);

        $this->view('admin/users', [
            'title'        => 'Operator Account Directory',
            'user'         => $user,
            'users'        => $users,
            'search'       => $search,
            'roleFilter'   => $roleFilter,
            'statusFilter' => $statusFilter,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'totalUsers'   => $totalUsers
        ]);
    }

    public function createUser(): void {
        $username = $this->getPost('username');
        $email = $this->getPost('email');
        $phone = $this->getPost('phone');
        $password = $this->getPost('password');
        $role = $this->getPost('role');

        if (empty($username) || empty($email) || empty($phone) || empty($password) || empty($role)) {
            $this->json(['status' => 'error', 'message' => 'All fields are required.'], 400);
        }

        try {
            // Check duplicates
            $exists = $this->db->fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            if ($exists) {
                $this->json(['status' => 'error', 'message' => 'Username or Email is already registered.'], 400);
            }

            // Insert user
            $passwordHash = \App\Core\PasswordManager::hash($password);
            $this->db->query(
                "INSERT INTO users (username, email, phone, password_hash, status) VALUES (?, ?, ?, ?, 'active')",
                [$username, $email, $phone, $passwordHash]
            );
            $newUserId = $this->db->getConnection()->lastInsertId();

            if ($role === 'admin') {
                $this->db->query("INSERT INTO admins (user_id) VALUES (?)", [$newUserId]);
            }

            $admin = Session::get('user');
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $this->db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'create_user', ?, ?, 0, 'Normal', 'low', ?)",
                [$admin['id'], $ip, $ua, "Administrator created operator account: '{$username}' (ID: {$newUserId})"]
            );

            $this->json(['status' => 'success', 'message' => 'Operator account created successfully.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Failed to create account.'], 500);
        }
    }

    public function updateUser(): void {
        $targetId = $this->getPost('user_id');
        $email = $this->getPost('email');
        $phone = $this->getPost('phone');
        $role = $this->getPost('role');
        $status = $this->getPost('status');

        if (empty($targetId) || empty($email) || empty($phone) || empty($role) || empty($status)) {
            $this->json(['status' => 'error', 'message' => 'All fields are required.'], 400);
        }

        $admin = Session::get('user');
        if ((int)$targetId === (int)$admin['id'] && $role !== 'admin') {
            $this->json(['status' => 'error', 'message' => 'Self role demotion is denied.'], 400);
        }

        try {
            // Update email & phone & status
            $this->db->query("UPDATE users SET email = ?, phone = ?, status = ? WHERE id = ?", [$email, $phone, $status, $targetId]);

            // Update role
            $isAdminNow = (bool)$this->db->fetchColumn("SELECT COUNT(*) FROM admins WHERE user_id = ?", [$targetId]);
            if ($role === 'admin' && !$isAdminNow) {
                $this->db->query("INSERT INTO admins (user_id) VALUES (?)", [$targetId]);
            } elseif ($role === 'user' && $isAdminNow) {
                $this->db->query("DELETE FROM admins WHERE user_id = ?", [$targetId]);
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $this->db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'update_user', ?, ?, 0, 'Normal', 'low', ?)",
                [$admin['id'], $ip, $ua, "Administrator modified profile metrics for operator ID: {$targetId}"]
            );

            $this->json(['status' => 'success', 'message' => 'Operator profile updated.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Profile update failure.'], 500);
        }
    }

    public function deleteUser(): void {
        $targetId = $this->getPost('user_id');
        if (empty($targetId)) {
            $this->json(['status' => 'error', 'message' => 'User ID is required.'], 400);
        }

        $admin = Session::get('user');
        if ((int)$targetId === (int)$admin['id']) {
            $this->json(['status' => 'error', 'message' => 'Self deletion is denied.'], 400);
        }

        try {
            $this->db->query("DELETE FROM users WHERE id = ?", [$targetId]);

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $this->db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'delete_user', ?, ?, 0, 'Normal', 'low', ?)",
                [$admin['id'], $ip, $ua, "Administrator deleted operator ID: {$targetId}"]
            );

            $this->json(['status' => 'success', 'message' => 'Operator account deleted.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Failed to delete account.'], 500);
        }
    }

    public function resetUserPassword(): void {
        $targetId = $this->getPost('user_id');
        $password = $this->getPost('password');

        if (empty($targetId) || empty($password)) {
            $this->json(['status' => 'error', 'message' => 'Parameters are required.'], 400);
        }

        try {
            $passwordHash = \App\Core\PasswordManager::hash($password);
            $this->db->query("UPDATE users SET password_hash = ? WHERE id = ?", [$passwordHash, $targetId]);

            $admin = Session::get('user');
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $this->db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'reset_password_override', ?, ?, 0, 'Normal', 'low', ?)",
                [$admin['id'], $ip, $ua, "Administrator reset passcode for operator ID: {$targetId}"]
            );

            $this->json(['status' => 'success', 'message' => 'Operator passcode updated.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Password reset failure.'], 500);
        }
    }

    public function toggleUserLock(): void {
        $targetId = $this->getPost('user_id');
        $action = $this->getPost('lock_action'); // 'lock' or 'unlock'

        if (empty($targetId) || empty($action)) {
            $this->json(['status' => 'error', 'message' => 'Parameters are required.'], 400);
        }

        $admin = Session::get('user');
        if ((int)$targetId === (int)$admin['id']) {
            $this->json(['status' => 'error', 'message' => 'Self lockout is denied.'], 400);
        }

        try {
            $newStatus = ($action === 'lock') ? 'locked' : 'active';
            $this->db->query("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $targetId]);

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $this->db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'toggle_user_lock', ?, ?, 0, 'Normal', 'low', ?)",
                [$admin['id'], $ip, $ua, "Administrator set lock state of operator ID: {$targetId} to {$newStatus}"]
            );

            $this->json(['status' => 'success', 'message' => 'Operator account status updated.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Failed to override lock state.'], 500);
        }
    }

    public function reportsDashboard(): void {
        $user = Session::get('user');

        $severity = $_GET['severity'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $where = ["l.risk_score > 0"];
        $params = [];

        if (!empty($severity)) {
            if ($severity === 'low') {
                $where[] = "l.risk_score < 30";
            } elseif ($severity === 'medium') {
                $where[] = "l.risk_score >= 30 AND l.risk_score < 70";
            } elseif ($severity === 'high') {
                $where[] = "l.risk_score >= 70 AND l.risk_score < 90";
            } elseif ($severity === 'critical') {
                $where[] = "l.risk_score >= 90";
            }
        }

        if (!empty($startDate)) {
            $where[] = "l.created_at >= ?";
            $params[] = $startDate . " 00:00:00";
        }
        if (!empty($endDate)) {
            $where[] = "l.created_at <= ?";
            $params[] = $endDate . " 23:59:59";
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        // Fetch logs
        $logs = $this->db->fetchAll(
            "SELECT l.*, u.username FROM activity_logs l 
             LEFT JOIN users u ON l.user_id = u.id 
             {$whereSql} 
             ORDER BY l.created_at DESC",
            $params
        );

        // Count severity levels
        $lowCount = 0; $medCount = 0; $highCount = 0; $critCount = 0;
        $totalRisk = 0;
        foreach ($logs as $log) {
            $r = (int)$log['risk_score'];
            $totalRisk += $r;
            if ($r < 30) $lowCount++;
            elseif ($r < 70) $medCount++;
            elseif ($r < 90) $highCount++;
            else $critCount++;
        }

        $avgRisk = count($logs) > 0 ? round($totalRisk / count($logs), 1) : 0;

        // Daily trend timeline (limit to last 15 days or range)
        $trendStats = $this->db->fetchAll(
            "SELECT DATE(l.created_at) as date, COUNT(*) as count 
             FROM activity_logs l 
             {$whereSql} 
             GROUP BY DATE(l.created_at) 
             ORDER BY DATE(l.created_at) ASC",
            $params
        );

        $this->view('admin/reports', [
            'title'       => 'Threat Intelligence Reports Center',
            'user'        => $user,
            'logs'        => $logs,
            'severity'    => $severity,
            'startDate'   => $startDate,
            'endDate'     => $endDate,
            'avgRisk'     => $avgRisk,
            'lowCount'    => $lowCount,
            'medCount'    => $medCount,
            'highCount'   => $highCount,
            'critCount'   => $critCount,
            'trendStats'  => $trendStats
        ]);
    }

    public function exportReportsCsv(): void {
        $severity = $_GET['severity'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $where = ["l.risk_score > 0"];
        $params = [];

        if (!empty($severity)) {
            if ($severity === 'low') {
                $where[] = "l.risk_score < 30";
            } elseif ($severity === 'medium') {
                $where[] = "l.risk_score >= 30 AND l.risk_score < 70";
            } elseif ($severity === 'high') {
                $where[] = "l.risk_score >= 70 AND l.risk_score < 90";
            } elseif ($severity === 'critical') {
                $where[] = "l.risk_score >= 90";
            }
        }

        if (!empty($startDate)) {
            $where[] = "l.created_at >= ?";
            $params[] = $startDate . " 00:00:00";
        }
        if (!empty($endDate)) {
            $where[] = "l.created_at <= ?";
            $params[] = $endDate . " 23:59:59";
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        $logs = $this->db->fetchAll(
            "SELECT l.*, u.username FROM activity_logs l 
             LEFT JOIN users u ON l.user_id = u.id 
             {$whereSql} 
             ORDER BY l.created_at DESC",
            $params
        );

        // Stream CSV headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=threat_report_' . date('Ymd_His') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Log ID', 'Timestamp', 'Operator', 'Action', 'IP Address', 'User Agent', 'AI Risk Score', 'Threat Classification', 'Details']);

        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['created_at'],
                $log['username'] ?? 'SYSTEM',
                $log['action'],
                $log['ip_address'],
                $log['user_agent'],
                $log['risk_score'] . '%',
                $log['threat_classification'],
                $log['threat_details'] ?: 'N/A'
            ]);
        }

        fclose($output);
        exit;
    }

    public function settingsDashboard(): void {
        $user = Session::get('user');
        $settingsService = new \App\Services\SettingsService();
        $settings = $settingsService->getAll();

        $this->view('admin/settings', [
            'title'    => 'System Configuration Console',
            'user'     => $user,
            'settings' => $settings
        ]);
    }

    public function updateSettings(): void {
        $admin = Session::get('user');
        $settingsService = new \App\Services\SettingsService();

        $keys = [
            'app_name', 'time_zone', 'theme', 'security_level', 
            'mfa_requirement', 'session_timeout', 'ai_detection_level', 
            'backup_retention_days', 'smtp_host', 'smtp_port', 'smtp_user', 
            'smtp_pass', 'encryption_algorithm'
        ];

        try {
            foreach ($keys as $key) {
                if (isset($_POST[$key])) {
                    $settingsService->set($key, $_POST[$key]);
                }
            }

            // Audit log settings updates
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $this->db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'update_system_settings', ?, ?, 0, 'Normal', 'low', ?)",
                [$admin['id'], $ip, $ua, "Administrator modified core system setting keys: " . implode(', ', $keys)]
            );

            $this->json(['status' => 'success', 'message' => 'System configuration parameters updated.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Failed to apply configurations: ' . $e->getMessage()], 500);
        }
    }

    public function analyticsDashboard(): void {
        $user = Session::get('user');

        // 1. User Growth
        $userGrowth = $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as count FROM users 
             GROUP BY DATE(created_at) ORDER BY date ASC LIMIT 30"
        );

        // 2. Threat Growth
        $threatGrowth = $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as count FROM activity_logs 
             WHERE risk_score > 0 GROUP BY DATE(created_at) ORDER BY date ASC LIMIT 30"
        );

        // 3. Encryption Usage
        $encryptionUsage = $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as count FROM encrypted_messages 
             GROUP BY DATE(created_at) ORDER BY date ASC LIMIT 30"
        );

        // 4. Logins Success vs Failed
        $loginSuccess = $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as count FROM activity_logs 
             WHERE action LIKE 'login_success%' GROUP BY DATE(created_at) ORDER BY date ASC LIMIT 30"
        );
        $loginFailed = $this->db->fetchAll(
            "SELECT DATE(attempt_time) as date, COUNT(*) as count FROM login_attempts 
             WHERE status = 'failed' GROUP BY DATE(attempt_time) ORDER BY date ASC LIMIT 30"
        );

        // 5. Risk Score Distribution
        $riskLow = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM activity_logs WHERE risk_score > 0 AND risk_score < 30");
        $riskMed = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM activity_logs WHERE risk_score >= 30 AND risk_score < 70");
        $riskHigh = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM activity_logs WHERE risk_score >= 70");

        // 6. Active sessions and alerts
        $totalSessions = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM sessions");
        $totalAlerts = (int)$this->db->fetchColumn("SELECT COUNT(*) FROM security_alerts");

        // 7. Monthly Activity Volume
        $monthlyActivity = $this->db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM activity_logs 
             GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month ASC LIMIT 12"
        );

        $this->view('admin/analytics', [
            'title'           => 'SIEM Analytics Dashboard',
            'user'            => $user,
            'userGrowth'      => $userGrowth,
            'threatGrowth'    => $threatGrowth,
            'encryptionUsage' => $encryptionUsage,
            'loginSuccess'    => $loginSuccess,
            'loginFailed'     => $loginFailed,
            'riskLow'         => $riskLow,
            'riskMed'         => $riskMed,
            'riskHigh'        => $riskHigh,
            'totalSessions'   => $totalSessions,
            'totalAlerts'     => $totalAlerts,
            'monthlyActivity' => $monthlyActivity
        ]);
    }

    public function widgetsShowroom(): void {
        $user = Session::get('user');

        $this->view('admin/widgets_showroom', [
            'title' => 'Stateful Reusable Widgets Showroom',
            'user'  => $user
        ]);
    }

    /**
     * Renders the enterprise session management dashboard
     */
    public function sessionsDashboard(): void {
        $user = Session::get('user');
        $profileService = new \App\Services\ProfileService();

        // 1. Fetch active sessions globally, joining user data
        $rawSessions = $this->db->fetchAll(
            "SELECT s.*, u.username, u.email FROM sessions s 
             LEFT JOIN users u ON s.user_id = u.id 
             ORDER BY s.last_activity DESC"
        );

        $sessions = [];
        $deviceStats = ['Desktop' => 0, 'Mobile' => 0, 'Tablet' => 0];
        $browserStats = ['Chrome' => 0, 'Firefox' => 0, 'Safari' => 0, 'Edge' => 0, 'Opera' => 0, 'Other' => 0];

        foreach ($rawSessions as $s) {
            $payload = json_decode($s['payload'], true);
            $loginMethod = $payload['login_method'] ?? 'credentials';
            $rotationCount = $payload['rotation_count'] ?? 0;

            $uaInfo = $profileService->parseUserAgent($s['user_agent']);
            $deviceType = $uaInfo['device'];
            $browserName = $uaInfo['browser'];

            // Aggregate metrics
            $deviceStats[$deviceType] = ($deviceStats[$deviceType] ?? 0) + 1;
            if (isset($browserStats[$browserName])) {
                $browserStats[$browserName]++;
            } else {
                $browserStats['Other']++;
            }

            $sessions[] = array_merge($s, [
                'os'             => $uaInfo['os'],
                'browser'        => $browserName,
                'device'         => $deviceType,
                'login_method'   => $loginMethod,
                'rotation_count' => $rotationCount
            ]);
        }

        // 2. Query concurrent sessions violation (> 1 active session link)
        $concurrentSessions = $this->db->fetchAll(
            "SELECT s.user_id, u.username, COUNT(*) as session_count FROM sessions s 
             INNER JOIN users u ON s.user_id = u.id 
             GROUP BY s.user_id, u.username 
             HAVING COUNT(*) > 1"
        );

        // 3. Fetch recent session security alerts
        $securityAlerts = $this->db->fetchAll(
            "SELECT sa.*, u.username FROM security_alerts sa 
             LEFT JOIN users u ON sa.user_id = u.id 
             WHERE sa.message LIKE '%session%' 
                OR sa.message LIKE '%device%' 
                OR sa.message LIKE '%IP%'
                OR sa.message LIKE '%MFA%'
             ORDER BY sa.created_at DESC LIMIT 10"
        );

        // 4. Fetch session history log timeline
        $sessionHistory = $this->db->fetchAll(
            "SELECT al.*, u.username FROM activity_logs al 
             LEFT JOIN users u ON al.user_id = u.id 
             WHERE al.action LIKE 'login_success%' 
                OR al.action = 'logout' 
                OR al.action LIKE 'session_revoked%' 
                OR al.action = 'session_rotated'
                OR al.action LIKE 'admin_session%'
                OR al.action LIKE 'admin_user_sessions%'
             ORDER BY al.created_at DESC LIMIT 20"
        );

        $this->view('admin/sessions', [
            'title'              => 'Enterprise Session Control Center',
            'user'               => $user,
            'sessions'           => $sessions,
            'deviceStats'        => $deviceStats,
            'browserStats'       => $browserStats,
            'concurrentSessions' => $concurrentSessions,
            'securityAlerts'     => $securityAlerts,
            'sessionHistory'     => $sessionHistory
        ]);
    }

    /**
     * Admin command: revokes a target session ID globally
     */
    public function terminateSessionGlobal(): void {
        $admin = Session::get('user');
        $sessionId = $this->getPost('session_id');

        if (empty($sessionId)) {
            $this->json(['status' => 'error', 'message' => 'Session ID is required.'], 400);
        }

        // Find session details to log
        $session = $this->db->fetch("SELECT user_id, ip_address FROM sessions WHERE id = ?", [$sessionId]);
        if (!$session) {
            $this->json(['status' => 'error', 'message' => 'Active session not found.'], 404);
        }

        try {
            $this->db->query("DELETE FROM sessions WHERE id = ?", [$sessionId]);

            // Log action in audit logs
            $activityLogger = new \App\Services\ActivityLogger(new \App\Repositories\AuditLogRepository());
            $activityLogger->log("admin_session_revoked (Target User ID: {$session['user_id']}, Session IP: {$session['ip_address']})", $admin['id']);

            $this->json(['status' => 'success', 'message' => 'Active browser session footprint terminated.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Admin command: clears all session links for a target operator user node
     */
    public function terminateAllSessions(): void {
        $admin = Session::get('user');
        $targetUserId = (int)$this->getPost('user_id');

        if (!$targetUserId) {
            $this->json(['status' => 'error', 'message' => 'Target user ID is required.'], 400);
        }

        try {
            $this->db->query("DELETE FROM sessions WHERE user_id = ?", [$targetUserId]);

            // Log action
            $activityLogger = new \App\Services\ActivityLogger(new \App\Repositories\AuditLogRepository());
            $activityLogger->log("admin_user_sessions_cleared (Target User ID: {$targetUserId})", $admin['id']);

            $this->json(['status' => 'success', 'message' => 'All active operator session links terminated successfully.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
