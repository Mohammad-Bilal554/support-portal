<?php

declare(strict_types=1);

/**
 * DatabaseSeeder
 *
 * Run from project root:
 *   php database/seeds/DatabaseSeeder.php
 *
 * Creates:
 *  - 1 Super Admin
 *  - 2 Sample Employees
 *  - 2 Sample Companies
 *  - 4 Sample Clients
 *  - Default ticket categories
 *  - Default settings
 */

define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/vendor/autoload.php';

// Load .env
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim(trim($v), '"\'');
        putenv(trim($k) . '=' . trim(trim($v), '"\''));
    }
}

function env(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Connect
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    env('DB_HOST', '127.0.0.1'),
    env('DB_PORT', '3306'),
    env('DB_DATABASE', 'support_portal')
);

try {
    $pdo = new PDO($dsn, env('DB_USERNAME', 'root'), env('DB_PASSWORD', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✅ Connected to database.\n";
} catch (PDOException $e) {
    echo "❌ DB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$now = date('Y-m-d H:i:s');

// ── Companies ─────────────────────────────────────────────────
echo "Seeding companies...\n";
$companies = [
    ['Acme Corporation',  'contact@acme.com',    '+1-555-0101', 'New York, USA',   'https://acme.com'],
    ['TechVision Ltd',    'info@techvision.com', '+44-20-5555', 'London, UK',      'https://techvision.com'],
];

$companyIds = [];
foreach ($companies as $c) {
    $pdo->prepare('INSERT IGNORE INTO companies (name,email,phone,address,website,is_active,created_at,updated_at) VALUES (?,?,?,?,?,1,?,?)')
        ->execute([$c[0],$c[1],$c[2],$c[3],$c[4],$now,$now]);
    $companyIds[] = $pdo->lastInsertId() ?: 1;
}
echo "  → " . count($companies) . " companies seeded.\n";

// ── Users ─────────────────────────────────────────────────────
echo "Seeding users...\n";

// Super Admin   password: Admin@12345
// Employees     password: Employee@123
// Clients       password: Client@123

$bcryptAdmin    = password_hash('Admin@12345',    PASSWORD_BCRYPT, ['cost' => 12]);
$bcryptEmployee = password_hash('Employee@123',  PASSWORD_BCRYPT, ['cost' => 12]);
$bcryptClient   = password_hash('Client@123',    PASSWORD_BCRYPT, ['cost' => 12]);

$users = [
    [null,           'Super',  'Admin',   'admin@support-portal.com',   $bcryptAdmin,    'super_admin'],
    [null,           'John',   'Smith',   'john.smith@support.com',      $bcryptEmployee, 'employee'],
    [null,           'Sarah',  'Connor',  'sarah.connor@support.com',    $bcryptEmployee, 'employee'],
    [$companyIds[0], 'Mike',   'Johnson', 'mike.johnson@acme.com',       $bcryptClient,   'client'],
    [$companyIds[0], 'Emily',  'Davis',   'emily.davis@acme.com',        $bcryptClient,   'client'],
    [$companyIds[1], 'Robert', 'Brown',   'robert.brown@techvision.com', $bcryptClient,   'client'],
    [$companyIds[1], 'Lisa',   'Wilson',  'lisa.wilson@techvision.com',  $bcryptClient,   'client'],
];

foreach ($users as $u) {
    $pdo->prepare('INSERT IGNORE INTO users (company_id,first_name,last_name,email,password,role,is_active,email_verified,created_at,updated_at) VALUES (?,?,?,?,?,?,1,1,?,?)')
        ->execute([$u[0],$u[1],$u[2],$u[3],$u[4],$u[5],$now,$now]);
}
echo "  → " . count($users) . " users seeded.\n";

// ── Categories ────────────────────────────────────────────────
echo "Seeding ticket categories...\n";
$categories = [
    ['Technical Support', '#0d6efd', 'Software, hardware, and technical issues'],
    ['Billing',           '#198754', 'Invoices, payments, and subscriptions'],
    ['General Inquiry',   '#6c757d', 'General questions and information'],
    ['Feature Request',   '#6610f2', 'Suggestions for new features'],
    ['Bug Report',        '#dc3545', 'Reporting software bugs or errors'],
    ['Account',           '#fd7e14', 'Account access and profile issues'],
];

foreach ($categories as $cat) {
    $pdo->prepare('INSERT IGNORE INTO ticket_categories (name,color,description,is_active,created_at) VALUES (?,?,?,1,?)')
        ->execute([$cat[0],$cat[1],$cat[2],$now]);
}
echo "  → " . count($categories) . " categories seeded.\n";

// ── Settings ──────────────────────────────────────────────────
echo "Seeding settings...\n";
$settings = [
    ['site_name',              'Support Portal',         'general'],
    ['support_email',          'support@example.com',    'general'],
    ['tickets_per_page',       '20',                     'tickets'],
    ['auto_close_days',        '7',                      'tickets'],
    ['email_notifications',    '1',                      'mail'],
    ['notify_new_ticket',      '1',                      'mail'],
    ['notify_ticket_assigned', '1',                      'mail'],
    ['notify_ticket_resolved', '1',                      'mail'],
];

foreach ($settings as $s) {
    $pdo->prepare('INSERT IGNORE INTO settings (key_name,value,group_name) VALUES (?,?,?)')
        ->execute($s);
}
echo "  → " . count($settings) . " settings seeded.\n";

echo "\n✅ Database seeding complete!\n";
echo "─────────────────────────────────────────\n";
echo "  Login Credentials:\n";
echo "  Super Admin  → admin@support-portal.com  / Admin@12345\n";
echo "  Employee 1   → john.smith@support.com    / Employee@123\n";
echo "  Employee 2   → sarah.connor@support.com  / Employee@123\n";
echo "  Client 1     → mike.johnson@acme.com     / Client@123\n";
echo "─────────────────────────────────────────\n";
