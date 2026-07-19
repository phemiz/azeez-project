<?php
namespace App\Models;

use App\Core\Model;
use App\Repositories\MessageRepository;

/**
 * Message Entity Model
 * Proxies operations to MessageRepository using Repository Pattern
 */
class Message extends Model {
    private MessageRepository $repo;

    public function __construct() {
        parent::__construct();
        $this->repo = new MessageRepository();
    }

    /**
     * Stores a new cryptographic envelope
     */
    public function create(int $senderId, string $recipient, string $ciphertext, string $iv, string $salt, string $signature): bool {
        return $this->repo->create($senderId, $recipient, $ciphertext, $iv, $salt, $signature);
    }

    /**
     * Fetches user's secure envelopes history
     */
    public function getUserMessages(int $userId, int $limit = 10): array {
        return $this->repo->getUserMessages($userId, $limit);
    }

    /**
     * Count secure envelopes sent by user
     */
    public function countUserMessages(int $userId): int {
        return $this->repo->countUserMessages($userId);
    }

    /**
     * Count total system envelopes
     */
    public function countAllMessages(): int {
        return $this->repo->countAllMessages();
    }
}
