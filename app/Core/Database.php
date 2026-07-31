<?php
declare(strict_types=1);
namespace App\Core;

class Database {
    private static ?Database $instance = null;
    private \PDO $pdo;

    public function __construct() { $this->connect(); }

    private function connect(): void {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            env('DB_HOST','127.0.0.1'), env('DB_PORT','3307'), env('DB_DATABASE','support_portal'));
        try {
            $this->pdo = new \PDO($dsn, env('DB_USERNAME','root'), env('DB_PASSWORD',''), [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } catch (\PDOException $e) {
            Logger::getInstance()->error('DB connection failed: '.$e->getMessage());
            throw new \RuntimeException('Database connection failed. Check your .env configuration.');
        }
    }
    public static function getInstance(): static {
        if (!static::$instance) static::$instance = new static();
        return static::$instance;
    }
    public function getPdo(): \PDO { return $this->pdo; }
    public function query(string $sql, array $params=[]): \PDOStatement {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            Logger::getInstance()->error("Query failed: $sql | ".json_encode($params).' | '.$e->getMessage());
            throw $e;
        }
    }
    public function fetchOne(string $sql, array $params=[]): ?array {
        $row = $this->query($sql,$params)->fetch(); return $row ?: null;
    }
    public function fetchAll(string $sql, array $params=[]): array {
        return $this->query($sql,$params)->fetchAll();
    }
    public function fetchColumn(string $sql, array $params=[], int $col=0): mixed {
        return $this->query($sql,$params)->fetchColumn($col);
    }
    public function insert(string $table, array $data): int|string {
        $cols  = array_keys($data);
        $phs   = array_map(fn($c)=>':'.$c, $cols);
        $sql   = "INSERT INTO `{$table}` (".implode(',',$cols).") VALUES (".implode(',',$phs).")";
        $this->query($sql,$data);
        return $this->pdo->lastInsertId();
    }
    public function update(string $table, array $data, array $where): int {
        $set   = array_map(fn($c)=>"`{$c}` = :set_{$c}", array_keys($data));
        $whr   = array_map(fn($c)=>"`{$c}` = :whr_{$c}", array_keys($where));
        $sql   = "UPDATE `{$table}` SET ".implode(',',$set)." WHERE ".implode(' AND ',$whr);
        $params= [];
        foreach ($data  as $k=>$v) $params["set_{$k}"]=$v;
        foreach ($where as $k=>$v) $params["whr_{$k}"]=$v;
        return $this->query($sql,$params)->rowCount();
    }
    public function delete(string $table, array $where): int {
        $whr = array_map(fn($c)=>"`{$c}` = :{$c}", array_keys($where));
        $sql = "DELETE FROM `{$table}` WHERE ".implode(' AND ',$whr);
        return $this->query($sql,$where)->rowCount();
    }
    public function exists(string $table, array $where): bool {
        $whr = array_map(fn($c)=>"`{$c}` = :{$c}", array_keys($where));
        $sql = "SELECT 1 FROM `{$table}` WHERE ".implode(' AND ',$whr)." LIMIT 1";
        return (bool)$this->fetchColumn($sql,$where);
    }
    public function count(string $table, array $where=[]): int {
        if (empty($where)) return (int)$this->fetchColumn("SELECT COUNT(*) FROM `{$table}`");
        $whr = array_map(fn($c)=>"`{$c}` = :{$c}", array_keys($where));
        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE ".implode(' AND ',$whr);
        return (int)$this->fetchColumn($sql,$where);
    }
    public function beginTransaction(): bool { return $this->pdo->beginTransaction(); }
    public function commit(): bool           { return $this->pdo->commit(); }
    public function rollBack(): bool         { return $this->pdo->rollBack(); }
    public function transaction(callable $cb): mixed {
        $this->beginTransaction();
        try { $r=$cb($this); $this->commit(); return $r; }
        catch(\Throwable $e) { $this->rollBack(); throw $e; }
    }
    public function paginate(string $sql, array $params=[], int $page=1, int $perPage=15): array {
        $total  = (int)$this->fetchColumn('SELECT COUNT(*) FROM ('.$sql.') AS _c',$params);
        $offset = ($page-1)*$perPage;
        $data   = $this->fetchAll($sql." LIMIT {$perPage} OFFSET {$offset}",$params);
        return ['data'=>$data,'total'=>$total,'per_page'=>$perPage,'current_page'=>$page,
                'last_page'=>(int)ceil($total/$perPage),'from'=>$total>0?$offset+1:0,'to'=>min($offset+$perPage,$total)];
    }
    public function lastInsertId(): string { return $this->pdo->lastInsertId(); }
}
