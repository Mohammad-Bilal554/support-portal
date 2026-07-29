<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

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

    public static function getAllActive(): array
    {
        return static::all(['is_active' => 1], 'name ASC');
    }

    public static function withUserCount(): array
    {
        return static::db()->fetchAll(
            "SELECT c.*, COUNT(u.id) as user_count
             FROM companies c
             LEFT JOIN users u ON u.company_id = c.id AND u.is_active = 1
             GROUP BY c.id
             ORDER BY c.name ASC"
        );
    }
}
