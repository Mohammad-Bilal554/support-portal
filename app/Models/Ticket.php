<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Ticket extends Model
{
    protected static string $table      = 'tickets';
    protected static string $primaryKey = 'id';

    protected static array $fillable = [
        'ticket_number','subject','description','status','priority',
        'category_id','company_id','created_by','assigned_to',
        'due_date','resolved_at','closed_at',
    ];

    protected static array $casts = [
        'id'          => 'integer',
        'category_id' => 'integer',
        'company_id'  => 'integer',
        'created_by'  => 'integer',
        'assigned_to' => 'integer',
    ];

    // Status workflow map
    public const STATUSES = [
        'open'               => 'Open',
        'assigned'           => 'Assigned',
        'in_progress'        => 'In Progress',
        'waiting_for_client' => 'Waiting for Client',
        'resolved'           => 'Resolved',
        'closed'             => 'Closed',
    ];

    public const PRIORITIES = [
        'low'      => 'Low',
        'medium'   => 'Medium',
        'high'     => 'High',
        'critical' => 'Critical',
    ];

    // Valid transitions per role
    public const TRANSITIONS = [
        'super_admin' => [
            'open'               => ['assigned','in_progress','resolved','closed'],
            'assigned'           => ['in_progress','waiting_for_client','resolved','closed'],
            'in_progress'        => ['waiting_for_client','resolved','closed'],
            'waiting_for_client' => ['in_progress','resolved','closed'],
            'resolved'           => ['closed','open'],
            'closed'             => ['open'],
        ],
        'employee' => [
            'open'               => ['assigned','in_progress'],
            'assigned'           => ['in_progress','waiting_for_client','resolved'],
            'in_progress'        => ['waiting_for_client','resolved'],
            'waiting_for_client' => ['in_progress','resolved'],
            'resolved'           => ['closed'],
            'closed'             => [],
        ],
        'client' => [
            'open'               => [],
            'assigned'           => [],
            'in_progress'        => [],
            'waiting_for_client' => ['in_progress'],
            'resolved'           => ['closed'],
            'closed'             => [],
        ],
    ];

    // ── Finders ──────────────────────────────────────────────────

    public static function findWithDetails(int $id): ?array
    {
        return static::db()->fetchOne(
            "SELECT t.*,
                    tc.name  AS category_name,
                    tc.color AS category_color,
                    c.name   AS company_name,
                    u1.first_name AS creator_first, u1.last_name AS creator_last,
                    u1.email      AS creator_email, u1.avatar     AS creator_avatar,
                    u2.first_name AS assignee_first, u2.last_name AS assignee_last,
                    u2.email      AS assignee_email, u2.avatar     AS assignee_avatar
             FROM tickets t
             LEFT JOIN ticket_categories tc ON tc.id = t.category_id
             LEFT JOIN companies          c  ON c.id  = t.company_id
             LEFT JOIN users              u1 ON u1.id = t.created_by
             LEFT JOIN users              u2 ON u2.id = t.assigned_to
             WHERE t.id = ?",
            [$id]
        );
    }

    public static function listPaginated(
        int    $page    = 1,
        int    $perPage = 20,
        array  $filters = [],
        string $role    = 'super_admin',
        int    $userId  = 0
    ): array {
        $db     = static::db();
        $where  = ['1=1'];
        $params = [];

        // Role-based visibility
        if ($role === 'client') {
            $where[]  = "t.created_by = ?";
            $params[] = $userId;
        } elseif ($role === 'employee') {
            if (!empty($filters['my_tickets'])) {
                $where[]  = "t.assigned_to = ?";
                $params[] = $userId;
            }
        }

        if (!empty($filters['search'])) {
            $s        = '%' . $filters['search'] . '%';
            $where[]  = "(t.subject LIKE ? OR t.ticket_number LIKE ? OR t.description LIKE ?)";
            $params   = array_merge($params, [$s, $s, $s]);
        }
        if (!empty($filters['status'])) {
            $where[]  = "t.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where[]  = "t.priority = ?";
            $params[] = $filters['priority'];
        }
        if (!empty($filters['category_id'])) {
            $where[]  = "t.category_id = ?";
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['company_id'])) {
            $where[]  = "t.company_id = ?";
            $params[] = $filters['company_id'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[]  = "t.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        $sql = "SELECT t.*,
                       tc.name  AS category_name,
                       tc.color AS category_color,
                       c.name   AS company_name,
                       u1.first_name AS creator_first, u1.last_name AS creator_last,
                       u2.first_name AS assignee_first, u2.last_name AS assignee_last
                FROM tickets t
                LEFT JOIN ticket_categories tc ON tc.id = t.category_id
                LEFT JOIN companies          c  ON c.id  = t.company_id
                LEFT JOIN users              u1 ON u1.id = t.created_by
                LEFT JOIN users              u2 ON u2.id = t.assigned_to
                WHERE " . implode(' AND ', $where) . "
                ORDER BY
                    FIELD(t.priority,'critical','high','medium','low'),
                    t.created_at DESC";

        return $db->paginate($sql, $params, $page, $perPage);
    }

    public static function generateNumber(): string
    {
        $db   = Database::getInstance();
        $year = date('Y');
        $last = $db->fetchColumn(
            "SELECT ticket_number FROM tickets WHERE ticket_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["TKT-{$year}-%"]
        );
        $seq = $last ? (int)substr($last, -5) + 1 : 1;
        return 'TKT-' . $year . '-' . str_pad((string)$seq, 5, '0', STR_PAD_LEFT);
    }

    public static function canTransition(string $from, string $to, string $role): bool
    {
        return in_array($to, static::TRANSITIONS[$role][$from] ?? [], true);
    }

    public static function getStatusHistory(int $ticketId): array
    {
        return static::db()->fetchAll(
            "SELECT h.*, u.first_name, u.last_name, u.avatar
             FROM ticket_status_history h
             LEFT JOIN users u ON u.id = h.changed_by
             WHERE h.ticket_id = ?
             ORDER BY h.created_at ASC",
            [$ticketId]
        );
    }

    public static function getAttachments(int $ticketId): array
    {
        return static::db()->fetchAll(
            "SELECT a.*, u.first_name, u.last_name
             FROM ticket_attachments a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.ticket_id = ? AND a.conversation_id IS NULL
             ORDER BY a.created_at ASC",
            [$ticketId]
        );
    }

    public static function getConversations(int $ticketId, bool $includeInternal = true): array
    {
        $sql = "SELECT c.*,
                       u.first_name, u.last_name, u.avatar, u.role AS user_role,
                       GROUP_CONCAT(
                           CONCAT(a.id,'|',a.original_name,'|',a.stored_name,'|',a.mime_type,'|',a.file_size)
                           SEPARATOR ';;'
                       ) AS attachments_raw
                FROM ticket_conversations c
                LEFT JOIN users u ON u.id = c.user_id
                LEFT JOIN ticket_attachments a ON a.conversation_id = c.id
                WHERE c.ticket_id = ?";

        if (!$includeInternal) {
            $sql .= " AND c.is_internal = 0";
        }

        $sql .= " GROUP BY c.id ORDER BY c.created_at ASC";

        $rows = static::db()->fetchAll($sql, [$ticketId]);

        // Parse attachments
        foreach ($rows as &$row) {
            $row['attachments'] = [];
            if (!empty($row['attachments_raw'])) {
                foreach (explode(';;', $row['attachments_raw']) as $raw) {
                    [$aid, $origName, $storedName, $mime, $size] = explode('|', $raw);
                    $row['attachments'][] = [
                        'id'            => $aid,
                        'original_name' => $origName,
                        'stored_name'   => $storedName,
                        'mime_type'     => $mime,
                        'file_size'     => $size,
                    ];
                }
            }
            unset($row['attachments_raw']);
        }

        return $rows;
    }
}
