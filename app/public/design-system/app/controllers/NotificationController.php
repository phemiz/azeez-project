<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\NotificationService;

/**
 * Stateful Notifications Action Controller
 * Implements AJAX routes to fetch unread counters, fetch unread item lists,
 * and toggle read states.
 */
class NotificationController extends Controller {
    private NotificationService $notificationService;

    public function __construct() {
        $this->notificationService = new NotificationService();
    }

    /**
     * Renders the user notifications history center
     */
    public function index(): void {
        $user = Session::get('user');
        $notifications = $this->notificationService->getHistory($user['id']);

        $this->view('user/notifications', [
            'title'         => 'System Notifications Desk',
            'user'          => $user,
            'notifications' => $notifications
        ]);
    }

    /**
     * Returns unread stats and items list for dynamic header badges
     */
    public function getUnread(): void {
        $user = Session::get('user');
        if (!$user) {
            $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $count = $this->notificationService->getUnreadCount($user['id']);
        $history = $this->notificationService->getHistory($user['id'], 5);

        $this->json([
            'status' => 'success',
            'unread_count' => $count,
            'latest' => $history
        ]);
    }

    /**
     * Toggles a single notification read state
     */
    public function read(): void {
        $user = Session::get('user');
        $notificationId = $this->getPost('notification_id');

        if (empty($notificationId)) {
            $this->json(['status' => 'error', 'message' => 'Notification ID required.'], 400);
        }

        $this->notificationService->markAsRead((int)$notificationId, $user['id']);
        $this->json(['status' => 'success', 'message' => 'Notification status toggled read.']);
    }

    /**
     * Toggles all notifications read state
     */
    public function readAll(): void {
        $user = Session::get('user');
        $this->notificationService->markAllAsRead($user['id']);
        $this->json(['status' => 'success', 'message' => 'All operator notifications toggled read.']);
    }
}
