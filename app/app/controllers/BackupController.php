<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\BackupService;

/**
 * Administrative Backup System Controller
 * Handles manual generation, AES-256 encryption triggers, secure downloads,
 * integrity verification, and database snapshot restores.
 */
class BackupController extends Controller {
    private BackupService $backupService;

    public function __construct() {
        $this->backupService = new BackupService();
    }

    /**
     * Renders the administrative backup console
     */
    public function index(): void {
        $user = Session::get('user');
        $backups = $this->backupService->getBackupHistory();

        $this->view('admin/backups', [
            'title'   => 'System Backup & Restoration Center',
            'user'    => $user,
            'backups' => $backups
        ]);
    }

    /**
     * Generates a new backup file
     */
    public function generate(): void {
        $admin = Session::get('user');
        $type = $this->getPost('backup_type', 'database');
        $encrypt = (bool)$this->getPost('encrypt', false);

        try {
            $filename = $this->backupService->generateBackup($type, $encrypt, $admin['id']);

            // Log activity
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $db = \App\Core\Database::getInstance();
            $db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'db_backup_create', ?, ?, 0, 'Normal', 'low', ?)",
                [$admin['id'], $ip, $ua, "Created backup file: " . $filename . " (Type: " . $type . ", Encrypted: " . ($encrypt ? 'YES' : 'NO') . ")"]
            );

            $this->json(['status' => 'success', 'message' => 'Manual backup snapshot created successfully: ' . $filename]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Restores database state from snapshot
     */
    public function restore(): void {
        $admin = Session::get('user');
        $filename = $this->getPost('filename');

        if (empty($filename)) {
            $this->json(['status' => 'error', 'message' => 'Filename parameter is required.'], 400);
        }

        try {
            $this->backupService->restoreBackup($filename);

            // Log restoration
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $db = \App\Core\Database::getInstance();
            $db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'db_backup_restore', ?, ?, 0, 'Normal', 'low', ?)",
                [$admin['id'], $ip, $ua, "Database restored from snapshot: " . $filename]
            );

            $this->json(['status' => 'success', 'message' => 'Database snapshot restored successfully. Page reload mandated.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Restoration failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Securely streams backup download, mitigating Directory Traversal attacks
     */
    public function download(): void {
        $filename = $_GET['filename'] ?? '';
        if (empty($filename)) {
            exit('Filename required.');
        }

        // Lock file access to backups directory
        $filename = basename($filename);
        $filePath = dirname(dirname(__DIR__)) . '/backups/' . $filename;

        if (!file_exists($filePath)) {
            http_response_code(404);
            exit('Snapshot file not found.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * Verifies file integrity checks
     */
    public function verify(): void {
        $filename = $this->getPost('filename');
        if (empty($filename)) {
            $this->json(['status' => 'error', 'message' => 'Filename parameter required.'], 400);
        }

        $result = $this->backupService->verifyBackup($filename);
        $this->json([
            'status'  => 'success',
            'check'   => $result['status'],
            'hash'    => $result['hash'] ?? 'N/A',
            'message' => $result['details']
        ]);
    }

    /**
     * Deletes a backup snapshot
     */
    public function delete(): void {
        $admin = Session::get('user');
        $filename = $this->getPost('filename');

        if (empty($filename)) {
            $this->json(['status' => 'error', 'message' => 'Filename required.'], 400);
        }

        $filename = basename($filename);
        $filePath = dirname(dirname(__DIR__)) . '/backups/' . $filename;

        try {
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $db = \App\Core\Database::getInstance();
            $db->query("DELETE FROM backup_history WHERE filename = ?", [$filename]);

            // Log deletion
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'db_backup_delete', ?, ?, 0, 'Normal', 'low', ?)",
                [$admin['id'], $ip, $ua, "Deleted backup snapshot: " . $filename]
            );

            $this->json(['status' => 'success', 'message' => 'Backup snapshot deleted successfully.']);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function showWizard(): void {
        $user = Session::get('user');
        $backups = $this->backupService->getBackupHistory();

        $this->view('admin/restore_wizard', [
            'title'   => 'Disaster Recovery Restore Wizard',
            'user'    => $user,
            'backups' => $backups
        ]);
    }
}
