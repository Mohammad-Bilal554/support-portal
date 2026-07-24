<?php

declare(strict_types=1);

namespace App\Core;

/**
 * BaseModel
 *
 * Active-Record-style base class.
 * All domain models extend this.
 *
 * Features:
 * - Auto-timestamps (created_at / updated_at)
 * - CRUD shorthand methods
 * - Soft deletes (optional)
 * - Relationships (hasMany / belongsTo)
 * - Mass-assignment guard ($fillable / $guarded)
 */
abstract class Model
{
    /** Database table name — override in child */
    protected static string $table = '';

    /** Primary key column */
    protected static string $primaryKey = 'id';

    /** Columns allowed for mass assignment */
    protected static array $fillable = [];

    /** Columns blocked from mass assignment (alternative to $fillable) */
    protected static array $guarded = ['id'];

    /** Columns to cast to specific types */
    protected static array $casts = [];

    /** Automatically manage created_at / updated_at */
    protected static bool $timestamps = true;

    /** Soft delete column name (null = disabled) */
    protected static ?string $softDelete = null;   // e.g. 'deleted_at'

    // ----------------------------------------------------------------
    // DB shortcut
    // ----------------------------------------------------------------

    protected static function db(): Database
    {
        return Database::getInstance();
    }

    protected static function table(): string
    {
        if (static::$table) {
            return static::$table;
        }
        // Auto-derive: UserProfile → user_profiles
        $class = (new \ReflectionClass(static::class))->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . 's';
    }

    // ----------------------------------------------------------------
    // Find helpers
    // ----------------------------------------------------------------

    /** Find by primary key */
    public static function find(int|string $id): ?array
    {
        $sql = sprintf(
            'SELECT * FROM `%s` WHERE `%s` = ? LIMIT 1',
            static::table(),
            static::$primaryKey
        );

        if (static::$softDelete) {
            $sql = sprintf(
                'SELECT * FROM `%s` WHERE `%s` = ? AND `%s` IS NULL LIMIT 1',
                static::table(),
                static::$primaryKey,
                static::$softDelete
            );
        }

        $row = static::db()->fetchOne($sql, [$id]);
        return $row ? static::castRow($row) : null;
    }

    /** Find or throw a 404 */
    public static function findOrFail(int|string $id): array
    {
        $row = static::find($id);

        if ($row === null) {
            http_response_code(404);
            throw new \RuntimeException(static::table() . " record [{$id}] not found.");
        }

        return $row;
    }

    /** Find first row matching conditions */
    public static function findBy(array $conditions): ?array
    {
        [$whereSQL, $params] = static::buildWhere($conditions);

        $sql = sprintf('SELECT * FROM `%s` WHERE %s LIMIT 1', static::table(), $whereSQL);
        $row = static::db()->fetchOne($sql, $params);
        return $row ? static::castRow($row) : null;
    }

    /** Get all rows (optionally filtered) */
    public static function all(array $conditions = [], string $orderBy = null, int $limit = null): array
    {
        $sql    = 'SELECT * FROM `' . static::table() . '`';
        $params = [];

        if (static::$softDelete) {
            $conditions[static::$softDelete . ' IS NULL'] = null;   // special sentinel
        }

        if (! empty($conditions)) {
            [$whereSQL, $params] = static::buildWhere($conditions);
            $sql .= ' WHERE ' . $whereSQL;
        }

        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $rows = static::db()->fetchAll($sql, $params);
        return array_map([static::class, 'castRow'], $rows);
    }

    /** Paginate results */
    public static function paginate(
        array  $conditions = [],
        int    $page       = 1,
        int    $perPage    = 15,
        string $orderBy    = null
    ): array {
        $sql    = 'SELECT * FROM `' . static::table() . '`';
        $params = [];

        if (! empty($conditions)) {
            [$whereSQL, $params] = static::buildWhere($conditions);
            $sql .= ' WHERE ' . $whereSQL;
        }

        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        $result = static::db()->paginate($sql, $params, $page, $perPage);
        $result['data'] = array_map([static::class, 'castRow'], $result['data']);
        return $result;
    }

    /** Count rows */
    public static function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            return static::db()->count(static::table());
        }

        [$whereSQL, $params] = static::buildWhere($conditions);
        $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', static::table(), $whereSQL);
        return (int) static::db()->fetchColumn($sql, $params);
    }

    /** Check existence */
    public static function exists(array $conditions): bool
    {
        return static::count($conditions) > 0;
    }

    // ----------------------------------------------------------------
    // Mutation
    // ----------------------------------------------------------------

    /** Insert a new row and return the inserted ID */
    public static function create(array $data): int|string
    {
        $data = static::filterFillable($data);

        if (static::$timestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $data['created_at'] ?? $now;
            $data['updated_at'] = $data['updated_at'] ?? $now;
        }

        return static::db()->insert(static::table(), $data);
    }

    /** Update rows matching $where */
    public static function updateWhere(array $data, array $where): int
    {
        if (static::$timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return static::db()->update(static::table(), $data, $where);
    }

    /** Update a single record by primary key */
    public static function updateById(int|string $id, array $data): int
    {
        return static::updateWhere($data, [static::$primaryKey => $id]);
    }

    /** Delete by primary key (or soft-delete) */
    public static function deleteById(int|string $id): int
    {
        if (static::$softDelete) {
            return static::updateWhere(
                [static::$softDelete => date('Y-m-d H:i:s')],
                [static::$primaryKey => $id]
            );
        }

        return static::db()->delete(static::table(), [static::$primaryKey => $id]);
    }

    /** Hard delete even if soft deletes are enabled */
    public static function forceDelete(int|string $id): int
    {
        return static::db()->delete(static::table(), [static::$primaryKey => $id]);
    }

    // ----------------------------------------------------------------
    // Raw query helpers
    // ----------------------------------------------------------------

    public static function raw(string $sql, array $params = []): array
    {
        return static::db()->fetchAll($sql, $params);
    }

    public static function rawOne(string $sql, array $params = []): ?array
    {
        return static::db()->fetchOne($sql, $params);
    }

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    /** hasMany: e.g. User::hasMany(Ticket::class, 'created_by', 'id') */
    public static function hasMany(
        string $relatedClass,
        string $foreignKey,
        int|string $localValue,
        string $localKey = null
    ): array {
        return $relatedClass::all([$foreignKey => $localValue]);
    }

    /** belongsTo: e.g. Ticket::belongsTo(User::class, 'created_by') */
    public static function belongsTo(string $relatedClass, int|string $foreignValue): ?array
    {
        return $relatedClass::find($foreignValue);
    }

    // ----------------------------------------------------------------
    // Mass-assignment protection
    // ----------------------------------------------------------------

    private static function filterFillable(array $data): array
    {
        if (! empty(static::$fillable)) {
            return array_intersect_key($data, array_flip(static::$fillable));
        }

        if (! empty(static::$guarded)) {
            return array_diff_key($data, array_flip(static::$guarded));
        }

        return $data;
    }

    // ----------------------------------------------------------------
    // Type casting
    // ----------------------------------------------------------------

    protected static function castRow(array $row): array
    {
        foreach (static::$casts as $column => $type) {
            if (! array_key_exists($column, $row)) {
                continue;
            }

            $row[$column] = match ($type) {
                'int', 'integer'  => (int)    $row[$column],
                'float', 'double' => (float)  $row[$column],
                'bool', 'boolean' => (bool)   $row[$column],
                'array', 'json'   => is_string($row[$column])
                    ? json_decode($row[$column], true)
                    : $row[$column],
                'datetime'        => $row[$column]
                    ? new \DateTimeImmutable($row[$column])
                    : null,
                default           => $row[$column],
            };
        }

        return $row;
    }

    // ----------------------------------------------------------------
    // WHERE builder
    // ----------------------------------------------------------------

    private static function buildWhere(array $conditions): array
    {
        $clauses = [];
        $params  = [];

        foreach ($conditions as $column => $value) {
            // Special: 'column IS NULL' => null
            if (str_ends_with($column, ' IS NULL') && $value === null) {
                $clauses[] = $column;
                continue;
            }

            if (is_array($value)) {
                // IN clause
                $placeholders = implode(', ', array_fill(0, count($value), '?'));
                $clauses[]    = "`{$column}` IN ({$placeholders})";
                $params       = array_merge($params, $value);
            } elseif ($value === null) {
                $clauses[] = "`{$column}` IS NULL";
            } else {
                $clauses[] = "`{$column}` = ?";
                $params[]  = $value;
            }
        }

        return [implode(' AND ', $clauses), $params];
    }
}
