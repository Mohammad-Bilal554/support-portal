<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;

/**
 * NotificationService
 *
 * Manages in-app notifications:
 * - Fetch unread count
 * - Mark as read
 * - List notifications for a user
 */
class NotificationService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getUnreadCount(int $userId): int
    {
        return (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    public function getRecent(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM notifications
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    public function markRead(int $notifId, int $userId): bool
    {
        return $this->db->update(
            'notifications',
            ['is_read' => 1],
            ['id' => $notifId, 'user_id' => $userId]
        ) > 0;
    }

    public function markAllRead(int $userId): void
    {
        $this->db->query(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
    }

    public function delete(int $notifId, int $userId): bool
    {
        return $this->db->delete('notifications', ['id' => $notifId, 'user_id' => $userId]) > 0;
    }

    public function getPaginated(int $userId, int $page = 1, int $perPage = 20): array
    {
        return $this->db->paginate(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC",
            [$userId],
            $page,
            $perPage
        );
    }
}
