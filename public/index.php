<?php

declare(strict_types=1);

/**
 * Front Controller
 *
 * The single entry point for ALL HTTP requests.
 * Bootstraps the application and dispatches the request.
 */

define('BASE_PATH', dirname(__DIR__));
define('START_TIME', microtime(true));
define('START_MEMORY', memory_get_usage());

// ── Autoloader ──────────────────────────────────────────────────────────────
$autoloader = BASE_PATH . '/vendor/autoload.php';

if (! file_exists($autoloader)) {
    http_response_code(503);
    echo '<h1>503 – Application Not Installed</h1>';
    echo '<p>Please run <code>composer install</code> in the project root.</p>';
    exit(1);
}

require_once $autoloader;

// ── Bootstrap & Run ─────────────────────────────────────────────────────────
$app = \App\Core\Application::getInstance(BASE_PATH);

// Share global helpers with all views
\App\Core\View::shareMany([
    'appName'  => env('APP_NAME', 'Support Portal'),
    'appUrl'   => env('APP_URL',  ''),
    'appDebug' => env('APP_DEBUG', false),
]);

$app->run();
