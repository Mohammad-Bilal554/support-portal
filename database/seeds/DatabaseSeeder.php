<?php
declare(strict_types=1);
/**
 * Database Seeder — run: php database/seeds/DatabaseSeeder.php
 */
define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/vendor/autoload.php';

// Load .env manually
foreach (file(BASE_PATH.'/.env', FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line),'#') || !str_contains($line,'=')) continue;
    [$k,$v] = explode('=',$line,2);
    $_ENV[trim($k)] = trim(trim($v),'"\'');
    putenv(trim($k).'='.trim(trim($v),'"\''));
}
function env(string $k, mixed $d=null): mixed { return $_ENV[$k] ?? getenv($k) ?: $d; }

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', env('DB_HOST','127.0.0.1'), env('DB_PORT','3306'), env('DB_DATABASE','support_portal')),
        env('DB_USERNAME','root'), env('DB_PASSWORD',''),
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connected to database.\n";
} catch (PDOException $e) { echo "❌ ".$e->getMessage()."\n"; exit(1); }

$now = date('Y-m-d H:i:s');

// Companies
echo "Seeding companies...\n";
$pdo->prepare('INSERT IGNORE INTO companies (name,email,phone,address,website,is_active,created_at,updated_at) VALUES (?,?,?,?,?,1,?,?)')
    ->execute(['Acme Corporation','contact@acme.com','+1-555-0101','New York, USA','https://acme.com',$now,$now]);
$comp1 = $pdo->lastInsertId();
$pdo->prepare('INSERT IGNORE INTO companies (name,email,phone,address,website,is_active,created_at,updated_at) VALUES (?,?,?,?,?,1,?,?)')
    ->execute(['TechVision Ltd','info@techvision.com','+44-20-5555','London, UK','https://techvision.com',$now,$now]);
$comp2 = $pdo->lastInsertId();
echo "  → 2 companies seeded.\n";

// Users
echo "Seeding users...\n";
$hAdmin = password_hash('Admin@123',    PASSWORD_BCRYPT, ['cost'=>12]);
$hEmp   = password_hash('Employee@123', PASSWORD_BCRYPT, ['cost'=>12]);
$hCli   = password_hash('Client@123',   PASSWORD_BCRYPT, ['cost'=>12]);
$users  = [
    [null,  'Super','Admin',  'admin@support-portal.com',   $hAdmin, 'super_admin'],
    [null,  'John', 'Smith',  'john.smith@support.com',     $hEmp,   'employee'],
    [null,  'Sarah','Connor', 'sarah.connor@support.com',   $hEmp,   'employee'],
    [$comp1,'Mike', 'Johnson','mike.johnson@acme.com',      $hCli,   'client'],
    [$comp1,'Emily','Davis',  'emily.davis@acme.com',       $hCli,   'client'],
    [$comp2,'Robert','Brown', 'robert.brown@techvision.com',$hCli,   'client'],
];
$s = $pdo->prepare('INSERT IGNORE INTO users (company_id,first_name,last_name,email,password,role,is_active,email_verified,created_at,updated_at) VALUES (?,?,?,?,?,?,1,1,?,?)');
foreach ($users as $u) { $s->execute([$u[0],$u[1],$u[2],$u[3],$u[4],$u[5],$now,$now]); }
echo "  → ".count($users)." users seeded.\n";

echo "\n✅ Seeding complete!\n";
echo "─────────────────────────────────────────────────────\n";
echo "  Super Admin  : admin@support-portal.com  / Admin@123\n";
echo "  Employee 1   : john.smith@support.com    / Employee@123\n";
echo "  Employee 2   : sarah.connor@support.com  / Employee@123\n";
echo "  Client 1     : mike.johnson@acme.com     / Client@123\n";
echo "─────────────────────────────────────────────────────\n";
