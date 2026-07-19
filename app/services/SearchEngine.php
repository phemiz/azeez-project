<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Session;

/**
 * Reusable Global Search Engine
 * Runs query maps across relational tables (users, activity logs, SMS encryptions, security alarms)
 * and houses keyword highlighting and search history cache.
 */
class SearchEngine {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Executes queries across all active database targets.
     */
    public function search(string $query, array $filters = []): array {
        $results = [
            'users'       => [],
            'logs'        => [],
            'messages'    => [],
            'alerts'      => []
        ];

        if (empty($query)) {
            return $results;
        }

        // Cache search history in active session
        $this->addHistory($query);

        $limit = 10;
        $likeQuery = "%{$query}%";

        // 1. Search Users
        if (empty($filters['target']) || $filters['target'] === 'users') {
            $results['users'] = $this->db->fetchAll(
                "SELECT id, username, email, phone, status, created_at FROM users 
                 WHERE username LIKE ? OR email LIKE ? OR phone LIKE ? 
                 ORDER BY created_at DESC LIMIT ?",
                [$likeQuery, $likeQuery, $likeQuery, $limit]
            );
        }

        // 2. Search Activity Logs
        if (empty($filters['target']) || $filters['target'] === 'logs') {
            $results['logs'] = $this->db->fetchAll(
                "SELECT l.*, u.username FROM activity_logs l 
                 LEFT JOIN users u ON l.user_id = u.id 
                 WHERE l.action LIKE ? OR l.ip_address LIKE ? OR l.threat_classification LIKE ? 
                 ORDER BY l.created_at DESC LIMIT ?",
                [$likeQuery, $likeQuery, $likeQuery, $limit]
            );
        }

        // 3. Search Encrypted Messages (Scoped to authorized sender or recipient)
        if (empty($filters['target']) || $filters['target'] === 'messages') {
            $user = Session::get('user');
            $userId = $user['id'] ?? 0;
            $username = $user['username'] ?? '';
            $phone = $user['phone'] ?? '';
            
            $numericPhone = preg_replace('/[^0-9]/', '', $phone);
            $shortPhone = $numericPhone;
            if (str_starts_with($numericPhone, '234') && strlen($numericPhone) > 3) {
                $shortPhone = substr($numericPhone, 3);
            }
            $localPhone = '0' . $shortPhone;

            $results['messages'] = $this->db->fetchAll(
                "SELECT id, recipient, 'AES-256-CBC' AS algorithm, 'Low' AS risk_grade, created_at 
                 FROM encrypted_messages 
                 WHERE (sender_id = ? 
                     OR recipient = ? 
                     OR recipient = ? 
                     OR recipient = ? 
                     OR recipient = ? 
                     OR recipient = ? 
                     OR (recipient LIKE ? AND LENGTH(?) > 5))
                   AND (recipient LIKE ?)
                 ORDER BY created_at DESC LIMIT ?",
                [
                    $userId,
                    $username,
                    $phone,
                    $numericPhone,
                    $shortPhone,
                    $localPhone,
                    '%' . $shortPhone,
                    $shortPhone,
                    $likeQuery,
                    $limit
                ]
            );
        }

        // 4. Search Security Alerts
        if (empty($filters['target']) || $filters['target'] === 'alerts') {
            $results['alerts'] = $this->db->fetchAll(
                "SELECT * FROM security_alerts 
                 WHERE message LIKE ? OR severity LIKE ? 
                 ORDER BY created_at DESC LIMIT ?",
                [$likeQuery, $likeQuery, $limit]
            );
        }

        return $results;
    }

    /**
     * Highlights search query matches.
     */
    public function highlight(string $text, string $query): string {
        if (empty($query)) {
            return htmlspecialchars($text);
        }
        return preg_replace(
            '/(' . preg_quote($query, '/') . ')/i',
            '<mark class="bg-cyan-500/20 text-cyan-300 px-0.5 rounded">$1</mark>',
            htmlspecialchars($text)
        );
    }

    /**
     * Cache recent search query inside session.
     */
    public function addHistory(string $query): void {
        $history = Session::get('search_history', []);
        
        // Remove duplicate entry if exists, then prepend
        if (($key = array_search($query, $history)) !== false) {
            unset($history[$key]);
        }
        
        array_unshift($history, $query);
        
        // Keep last 10 entries
        $history = array_slice($history, 0, 10);
        Session::set('search_history', $history);
    }

    /**
     * Returns history catalog.
     */
    public function getHistory(): array {
        return Session::get('search_history', []);
    }
}
