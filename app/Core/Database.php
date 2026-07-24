<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Database
 *
 * PDO wrapper with:
 * - Connection pooling (singleton per config)
 * - Fluent query builder helpers
 * - Prepared statement execution
 * - Transaction support
 * - Query logging (debug mode)
 */
class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;
    private array $queryLog = [];
    private bool  $logging;

    // ----------------------------------------------------------------
    // Connection
    // ----------------------------------------------------------------

    public function __construct()
    {
        $this->logging = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
        $this->connect();
    }

    private function connect(): void
    {
        $host    = env('DB_HOST',     '127.0.0.1');
        $port    = env('DB_PORT',     '3306');
        $dbname  = env('DB_DATABASE', 'support_portal');
        $user    = env('DB_USERNAME', 'root');
        $pass    = env('DB_PASSWORD', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            Logger::getInstance()->error('Database connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Database connection failed. Check your .env configuration.');
        }
    }

    /** Get the singleton instance */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /** Get the raw PDO instance */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    // ----------------------------------------------------------------
    // Core query execution
    // ----------------------------------------------------------------

    /**
     * Execute a prepared statement and return the PDOStatement.
     *
     * @param string $sql    SQL with ? or :name placeholders
     * @param array  $params Bound parameters
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $start = microtime(true);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            if ($this->logging) {
                $this->queryLog[] = [
                    'sql'    => $sql,
                    'params' => $params,
                    'time'   => round((microtime(true) - $start) * 1000, 2) . 'ms',
                ];
            }

            return $stmt;

        } catch (\PDOException $e) {
            Logger::getInstance()->error("Query failed: {$sql} | Params: " . json_encode($params));
            Logger::getInstance()->error($e->getMessage());
            throw $e;
        }
    }

    // ----------------------------------------------------------------
    // SELECT helpers
    // ----------------------------------------------------------------

    /** Fetch a single row as assoc array */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    /** Fetch all rows */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /** Fetch a single column value */
    public function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        return $this->query($sql, $params)->fetchColumn($column);
    }

    /** Fetch all rows as objects of a class */
    public function fetchAllAs(string $sql, string $class, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        $stmt->setFetchMode(\PDO::FETCH_CLASS, $class);
        return $stmt->fetchAll();
    }

    // ----------------------------------------------------------------
    // INSERT / UPDATE / DELETE helpers
    // ----------------------------------------------------------------

    /**
     * Insert a row and return the last insert ID.
     *
     * @param string $table
     * @param array  $data  ['column' => value]
     */
    public function insert(string $table, array $data): int|string
    {
        $columns      = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($table),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update rows matching $where conditions.
     *
     * @param string $table
     * @param array  $data        Columns to update
     * @param array  $where       ['column' => value] conditions (AND)
     * @return int   Affected rows
     */
    public function update(string $table, array $data, array $where): int
    {
        $setClauses   = array_map(fn($c) => "{$c} = :set_{$c}", array_keys($data));
        $whereClauses = array_map(fn($c) => "{$c} = :whr_{$c}", array_keys($where));

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->quoteIdentifier($table),
            implode(', ', $setClauses),
            implode(' AND ', $whereClauses)
        );

        // Prefix param keys to avoid collision
        $params = [];
        foreach ($data  as $k => $v) { $params["set_{$k}"] = $v; }
        foreach ($where as $k => $v) { $params["whr_{$k}"] = $v; }

        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Delete rows matching $where conditions.
     *
     * @param string $table
     * @param array  $where ['column' => value]
     * @return int   Affected rows
     */
    public function delete(string $table, array $where): int
    {
        $whereClauses = array_map(fn($c) => "{$c} = :{$c}", array_keys($where));

        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $this->quoteIdentifier($table),
            implode(' AND ', $whereClauses)
        );

        return $this->query($sql, $where)->rowCount();
    }

    /** Check if a row exists */
    public function exists(string $table, array $where): bool
    {
        $whereClauses = array_map(fn($c) => "{$c} = :{$c}", array_keys($where));

        $sql = sprintf(
            'SELECT 1 FROM %s WHERE %s LIMIT 1',
            $this->quoteIdentifier($table),
            implode(' AND ', $whereClauses)
        );

        return (bool) $this->fetchColumn($sql, $where);
    }

    /** Count rows matching conditions */
    public function count(string $table, array $where = []): int
    {
        if (empty($where)) {
            $sql = "SELECT COUNT(*) FROM {$this->quoteIdentifier($table)}";
            return (int) $this->fetchColumn($sql);
        }

        $whereClauses = array_map(fn($c) => "{$c} = :{$c}", array_keys($where));
        $sql = sprintf(
            'SELECT COUNT(*) FROM %s WHERE %s',
            $this->quoteIdentifier($table),
            implode(' AND ', $whereClauses)
        );

        return (int) $this->fetchColumn($sql, $where);
    }

    // ----------------------------------------------------------------
    // Transactions
    // ----------------------------------------------------------------

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Execute a callable within a transaction.
     * Automatically commits on success, rolls back on exception.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    // ----------------------------------------------------------------
    // Pagination
    // ----------------------------------------------------------------

    /**
     * Paginate a query.
     *
     * @return array ['data' => [], 'total' => int, 'per_page' => int,
     *                'current_page' => int, 'last_page' => int]
     */
    public function paginate(
        string $sql,
        array  $params   = [],
        int    $page     = 1,
        int    $perPage  = 15
    ): array {
        // Count total
        $countSql = 'SELECT COUNT(*) FROM (' . $sql . ') AS _count_query';
        $total    = (int) $this->fetchColumn($countSql, $params);

        $offset   = ($page - 1) * $perPage;
        $pagedSql = $sql . " LIMIT {$perPage} OFFSET {$offset}";
        $data     = $this->fetchAll($pagedSql, $params);

        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    // ----------------------------------------------------------------
    // Utilities
    // ----------------------------------------------------------------

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    public function clearQueryLog(): void
    {
        $this->queryLog = [];
    }
}
