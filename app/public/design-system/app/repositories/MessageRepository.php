<?php
namespace App\Repositories;

use App\Core\Repository;

/**
 * Repository for Encrypted Messages table operations
 */
class MessageRepository extends Repository {
    protected string $table = 'encrypted_messages';

    /**
     * Lists user encrypted transmission envelopes.
     */
    public function getUserMessages(int $userId, int $limit = 10): array {
        return $this->query()
            ->where('sender_id', '=', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculates user encrypted transmission totals.
     */
    public function countUserMessages(int $userId): int {
        $db = \App\Core\Database::getInstance();
        return (int)$db->fetchColumn("SELECT COUNT(*) FROM `encrypted_messages` WHERE sender_id = ?", [$userId]);
    }

    /**
     * Calculates total database transmissions.
     */
    public function countAllMessages(): int {
        $db = \App\Core\Database::getInstance();
        return (int)$db->fetchColumn("SELECT COUNT(*) FROM `encrypted_messages`");
    }
}
