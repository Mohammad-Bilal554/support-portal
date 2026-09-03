<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    private NotificationService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new NotificationService();
    }

    // GET /api/notifications/unread-count
    public function unreadCount(Request $request): string
    {
        $this->requireLogin();
        $count = $this->service->getUnreadCount((int)$this->authId());
        return $this->json(['count' => $count]);
    }

    // GET /api/notifications
    public function index(Request $request): string
    {
        $this->requireLogin();
        $notifications = $this->service->getRecent((int)$this->authId(), 10);
        return $this->json(['success' => true, 'data' => $notifications]);
    }

    // POST /api/notifications/{id}/read
    public function markRead(Request $request, string $id): string
    {
        $this->requireLogin();
        $result = $this->service->markRead((int)$id, (int)$this->authId());
        return $this->json(['success' => $result]);
    }

    // POST /api/notifications/read-all
    public function markAllRead(Request $request): string
    {
        $this->requireLogin();
        $this->service->markAllRead((int)$this->authId());
        return $this->json(['success' => true]);
    }
}
