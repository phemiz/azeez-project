<?php
namespace App\Services;

use App\Core\Database;

/**
 * Enterprise Stateful Notification Service
 * Dispatches and manages in-app notifications, security alerts, warnings, and messages
 * for standard operator nodes and administrative control hubs.
 */
class NotificationService {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Dispatches a stateful notification to a targeted user node.
     */
    public function send(int $userId, string $title, string $message, string $type = 'info'): void {
        $this->db->query(
            "INSERT INTO notifications (user_id, title, message, type, is_read) VALUES (?, ?, ?, ?, 0)",
            [$userId, $title, $message, $type]
        );
    }

    /**
     * Counts unread notifications active for a targeted user.
     */
    public function getUnreadCount(int $userId): int {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    /**
     * Compiles complete notifications history cataloged for a targeted user.
     */
    public function getHistory(int $userId, int $limit = 50): array {
        return $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Marks a targeted notification status as read.
     */
    public function markAsRead(int $notificationId, int $userId): void {
        $this->db->query(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
            [$notificationId, $userId]
        );
    }

    /**
     * Marks all notifications logged for a targeted user as read.
     */
    public function markAllAsRead(int $userId): void {
        $this->db->query(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ?",
            [$userId]
        );
    }
}
