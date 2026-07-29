<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class User extends Model
{
    protected static string $table      = 'users';
    protected static string $primaryKey = 'id';

    protected static array $fillable = [
        'company_id','first_name','last_name','email','password',
        'role','avatar','phone','is_active','email_verified','last_login',
    ];

    protected static array $casts = [
        'id'             => 'integer',
        'company_id'     => 'integer',
        'is_active'      => 'boolean',
        'email_verified' => 'boolean',
    ];

    // ── Finders ──────────────────────────────────────────────────

    public static function findByEmail(string $email): ?array
    {
        return static::findBy(['email' => $email]);
    }

    public static function findActive(int $id): ?array
    {
        return static::findBy(['id' => $id, 'is_active' => 1]);
    }

    /**
     * Paginated list with optional filters.
     */
    public static function listPaginated(
        int    $page     = 1,
        int    $perPage  = 20,
        array  $filters  = [],
        string $orderBy  = 'u.created_at DESC'
    ): array {
        $db     = static::db();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[]  = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params   = array_merge($params, [$s, $s, $s]);
        }
        if (!empty($filters['role'])) {
            $where[]  = "u.role = ?";
            $params[] = $filters['role'];
        }
        if (!empty($filters['company_id'])) {
            $where[]  = "u.company_id = ?";
            $params[] = $filters['company_id'];
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[]  = "u.is_active = ?";
            $params[] = (int)$filters['is_active'];
        }

        $sql = "SELECT u.*, c.name as company_name
                FROM users u
                LEFT JOIN companies c ON c.id = u.company_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$orderBy}";

        return $db->paginate($sql, $params, $page, $perPage);
    }

    public static function getAllEmployees(): array
    {
        return static::db()->fetchAll(
            "SELECT * FROM users
             WHERE role IN ('super_admin','employee') AND is_active=1
             ORDER BY first_name"
        );
    }

    public static function getAllClients(): array
    {
        return static::db()->fetchAll(
            "SELECT u.*, c.name as company_name
             FROM users u
             LEFT JOIN companies c ON c.id = u.company_id
             WHERE u.role='client' AND u.is_active=1
             ORDER BY u.first_name"
        );
    }

    public static function getByCompany(int $companyId): array
    {
        return static::all(['company_id' => $companyId, 'is_active' => 1], 'first_name ASC');
    }

    public static function countByRole(): array
    {
        $rows = static::db()->fetchAll(
            "SELECT role, COUNT(*) as total FROM users WHERE is_active=1 GROUP BY role"
        );
        $result = ['super_admin' => 0, 'employee' => 0, 'client' => 0];
        foreach ($rows as $r) {
            $result[$r['role']] = (int)$r['total'];
        }
        return $result;
    }

    // ── Auth helpers ──────────────────────────────────────────────

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => (int)env('BCRYPT_ROUNDS', 12)]);
    }

    public static function updateLastLogin(int $id): void
    {
        static::updateById($id, ['last_login' => date('Y-m-d H:i:s')]);
    }

    public static function emailExists(string $email, int $excludeId = 0): bool
    {
        $sql    = "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?";
        return (int)static::db()->fetchColumn($sql, [$email, $excludeId]) > 0;
    }

    // ── Display helpers ───────────────────────────────────────────

    public static function fullName(array $user): string
    {
        return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    }

    public static function avatarUrl(array $user): string
    {
        if (!empty($user['avatar'])) {
            return url('assets/uploads/avatars/' . $user['avatar']);
        }
        return 'https://ui-avatars.com/api/?name='
            . urlencode(static::fullName($user))
            . '&background=0d6efd&color=fff&size=128&bold=true';
    }

    // ── Brute-force protection ────────────────────────────────────

    public static function getLoginAttempts(string $email, string $ip): int
    {
        $key = 'login_attempts_' . md5($email . $ip);
        $val = static::db()->fetchOne(
            "SELECT value FROM settings WHERE key_name = ? LIMIT 1", [$key]
        );
        if (!$val) return 0;
        $data = json_decode($val['value'], true);
        if (time() - ($data['time'] ?? 0) > 900) return 0;
        return (int)($data['count'] ?? 0);
    }

    public static function incrementLoginAttempts(string $email, string $ip): void
    {
        $db      = static::db();
        $key     = 'login_attempts_' . md5($email . $ip);
        $current = static::getLoginAttempts($email, $ip);
        $data    = json_encode(['count' => $current + 1, 'time' => time()]);
        $exists  = $db->fetchOne("SELECT id FROM settings WHERE key_name = ?", [$key]);
        if ($exists) {
            $db->update('settings', ['value' => $data], ['key_name' => $key]);
        } else {
            $db->insert('settings', ['key_name' => $key, 'value' => $data, 'group_name' => 'security']);
        }
    }

    public static function clearLoginAttempts(string $email, string $ip): void
    {
        static::db()->delete('settings', ['key_name' => 'login_attempts_' . md5($email . $ip)]);
    }

    public static function isLockedOut(string $email, string $ip): bool
    {
        return static::getLoginAttempts($email, $ip) >= 5;
    }
}
