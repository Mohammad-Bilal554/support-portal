<?php
declare(strict_types=1);
namespace App\Core;

abstract class Model {
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected static array $fillable = [];
    protected static array $guarded  = ['id'];
    protected static array $casts    = [];
    protected static bool  $timestamps = true;
    protected static ?string $softDelete = null;

    protected static function db(): Database { return Database::getInstance(); }
    protected static function table(): string {
        if (static::$table) return static::$table;
        $class = (new \ReflectionClass(static::class))->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/','_$0',$class)).'s';
    }
    public static function find(int|string $id): ?array {
        $sql = "SELECT * FROM `".static::table()."` WHERE `".static::$primaryKey."` = ? LIMIT 1";
        $row = static::db()->fetchOne($sql,[$id]);
        return $row ? static::castRow($row) : null;
    }
    public static function findOrFail(int|string $id): array {
        $row = static::find($id);
        if ($row===null) { http_response_code(404); throw new \RuntimeException(static::table()." [{$id}] not found."); }
        return $row;
    }
    public static function findBy(array $conditions): ?array {
        [$where,$params] = static::buildWhere($conditions);
        $row = static::db()->fetchOne("SELECT * FROM `".static::table()."` WHERE {$where} LIMIT 1",$params);
        return $row ? static::castRow($row) : null;
    }
    public static function all(array $conditions=[], string $orderBy=null, int $limit=null): array {
        $sql="SELECT * FROM `".static::table()."`"; $params=[];
        if (!empty($conditions)) { [$where,$params]=static::buildWhere($conditions); $sql.=" WHERE {$where}"; }
        if ($orderBy) $sql.=" ORDER BY {$orderBy}";
        if ($limit)   $sql.=" LIMIT ".(int)$limit;
        return array_map([static::class,'castRow'], static::db()->fetchAll($sql,$params));
    }
    public static function count(array $conditions=[]): int {
        if (empty($conditions)) return static::db()->count(static::table());
        [$where,$params]=static::buildWhere($conditions);
        return (int)static::db()->fetchColumn("SELECT COUNT(*) FROM `".static::table()."` WHERE {$where}",$params);
    }
    public static function exists(array $conditions): bool { return static::count($conditions)>0; }
    public static function create(array $data): int|string {
        $data=static::filterFillable($data);
        if (static::$timestamps) { $now=date('Y-m-d H:i:s'); $data['created_at']??=$now; $data['updated_at']??=$now; }
        return static::db()->insert(static::table(),$data);
    }
    public static function updateWhere(array $data, array $where): int {
        if (static::$timestamps) $data['updated_at']=date('Y-m-d H:i:s');
        return static::db()->update(static::table(),$data,$where);
    }
    public static function updateById(int|string $id, array $data): int {
        return static::updateWhere($data,[static::$primaryKey=>$id]);
    }
    public static function deleteById(int|string $id): int {
        if (static::$softDelete) return static::updateWhere([static::$softDelete=>date('Y-m-d H:i:s')],[static::$primaryKey=>$id]);
        return static::db()->delete(static::table(),[static::$primaryKey=>$id]);
    }
    public static function paginate(array $conditions=[], int $page=1, int $perPage=15, string $orderBy=null): array {
        $sql="SELECT * FROM `".static::table()."`"; $params=[];
        if (!empty($conditions)) { [$where,$params]=static::buildWhere($conditions); $sql.=" WHERE {$where}"; }
        if ($orderBy) $sql.=" ORDER BY {$orderBy}";
        $result=static::db()->paginate($sql,$params,$page,$perPage);
        $result['data']=array_map([static::class,'castRow'],$result['data']);
        return $result;
    }
    public static function raw(string $sql, array $params=[]): array { return static::db()->fetchAll($sql,$params); }
    public static function rawOne(string $sql, array $params=[]): ?array { return static::db()->fetchOne($sql,$params); }
    private static function filterFillable(array $data): array {
        if (!empty(static::$fillable)) return array_intersect_key($data,array_flip(static::$fillable));
        if (!empty(static::$guarded))  return array_diff_key($data,array_flip(static::$guarded));
        return $data;
    }
    protected static function castRow(array $row): array {
        foreach (static::$casts as $col=>$type) {
            if (!array_key_exists($col,$row)) continue;
            $row[$col] = match($type) {
                'int','integer'=>(int)$row[$col],'float','double'=>(float)$row[$col],
                'bool','boolean'=>(bool)$row[$col],
                'array','json'=>is_string($row[$col])?json_decode($row[$col],true):$row[$col],
                default=>$row[$col],
            };
        }
        return $row;
    }
    private static function buildWhere(array $conditions): array {
        $clauses=[]; $params=[];
        foreach ($conditions as $col=>$value) {
            if (is_array($value)) { $phs=implode(',',array_fill(0,count($value),'?')); $clauses[]="`{$col}` IN ({$phs})"; $params=array_merge($params,$value); }
            elseif ($value===null) { $clauses[]="`{$col}` IS NULL"; }
            else { $clauses[]="`{$col}` = ?"; $params[]=$value; }
        }
        return [implode(' AND ',$clauses),$params];
    }
}
