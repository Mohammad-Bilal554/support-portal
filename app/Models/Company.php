<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Company extends Model
{
    protected static string $table      = 'companies';
    protected static string $primaryKey = 'id';

    protected static array $fillable = [
        'name','email','phone','address','website','logo','is_active',
    ];

    protected static array $casts = [
        'id'        => 'integer',
        'is_active' => 'boolean',
    ];

    // ── Finders ──────────────────────────────────────────────────

    public static function getAllActive(): array
    {
        return static::all(['is_active' => 1], 'name ASC');
    }

    /**
     * Paginated list with optional filters.
     */
    public static function listPaginated(
        int    $page    = 1,
        int    $perPage = 15,
        array  $filters = []
    ): array {
        $db     = static::db();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $s        = '%' . $filters['search'] . '%';
            $where[]  = "(c.name LIKE ? OR c.email LIKE ? OR c.website LIKE ?)";
            $params   = array_merge($params, [$s, $s, $s]);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[]  = "c.is_active = ?";
            $params[] = (int)$filters['is_active'];
        }

        $sql = "SELECT c.*,
                       COUNT(DISTINCT u.id)  AS user_count,
                       COUNT(DISTINCT t.id)  AS ticket_count,
                       SUM(CASE WHEN t.status NOT IN ('resolved','closed') THEN 1 ELSE 0 END) AS open_tickets
                FROM companies c
                LEFT JOIN users   u ON u.company_id = c.id AND u.is_active = 1
                LEFT JOIN tickets t ON t.company_id = c.id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY c.id
                ORDER BY c.name ASC";

        return $db->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Single company with stats.
     */
    public static function findWithStats(int $id): ?array
    {
        return static::db()->fetchOne(
            "SELECT c.*,
                    COUNT(DISTINCT u.id)  AS user_count,
                    COUNT(DISTINCT t.id)  AS ticket_count,
                    SUM(CASE WHEN t.status = 'open'        THEN 1 ELSE 0 END) AS open_tickets,
                    SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_tickets,
                    SUM(CASE WHEN t.status = 'resolved'    THEN 1 ELSE 0 END) AS resolved_tickets
             FROM companies c
             LEFT JOIN users   u ON u.company_id = c.id AND u.is_active = 1
             LEFT JOIN tickets t ON t.company_id = c.id
             WHERE c.id = ?
             GROUP BY c.id",
            [$id]
        );
    }

    /**
     * Get all users belonging to a company.
     */
    public static function getUsers(int $companyId): array
    {
        return static::db()->fetchAll(
            "SELECT u.*, COUNT(t.id) as ticket_count
             FROM users u
             LEFT JOIN tickets t ON t.created_by = u.id
             WHERE u.company_id = ? AND u.is_active = 1
             GROUP BY u.id
             ORDER BY u.first_name ASC",
            [$companyId]
        );
    }

    /**
     * Get recent tickets for a company.
     */
    public static function getTickets(int $companyId, int $limit = 10): array
    {
        return static::db()->fetchAll(
            "SELECT t.*, u.first_name, u.last_name,
                    tc.name AS category_name
             FROM tickets t
             LEFT JOIN users u            ON u.id  = t.created_by
             LEFT JOIN ticket_categories tc ON tc.id = t.category_id
             WHERE t.company_id = ?
             ORDER BY t.created_at DESC
             LIMIT ?",
            [$companyId, $limit]
        );
    }

    /**
     * Summary counts for dashboard.
     */
    public static function getSummary(): array
    {
        $db = static::db();
        return [
            'total'    => (int)$db->fetchColumn("SELECT COUNT(*) FROM companies"),
            'active'   => (int)$db->fetchColumn("SELECT COUNT(*) FROM companies WHERE is_active=1"),
            'inactive' => (int)$db->fetchColumn("SELECT COUNT(*) FROM companies WHERE is_active=0"),
        ];
    }

    // ── Display helpers ───────────────────────────────────────────

    public static function logoUrl(array $company): string
    {
        if (!empty($company['logo'])) {
            return url('assets/uploads/logos/' . $company['logo']);
        }
        // Generate initials-based placeholder
        $initials = strtoupper(substr($company['name'] ?? 'C', 0, 2));
        return 'https://ui-avatars.com/api/?name='
            . urlencode($company['name'] ?? 'Company')
            . '&background=0d6efd&color=fff&size=128&bold=true&length=2';
    }

    public static function emailExists(string $email, int $excludeId = 0): bool
    {
        return (int)static::db()->fetchColumn(
            "SELECT COUNT(*) FROM companies WHERE email = ? AND id != ?",
            [$email, $excludeId]
        ) > 0;
    }
}
