<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class TicketCategory extends Model
{
    protected static string $table      = 'ticket_categories';
    protected static string $primaryKey = 'id';

    protected static array $fillable = ['name','color','description','is_active'];

    protected static array $casts = [
        'id'        => 'integer',
        'is_active' => 'boolean',
    ];

    public static function getAllActive(): array
    {
        return static::all(['is_active' => 1], 'name ASC');
    }
}
