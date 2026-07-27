<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
define('START_TIME', microtime(true));

$autoloader = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    http_response_code(503);
    echo '<h1>503 – Run <code>composer install</code> first.</h1>';
    exit(1);
}
require_once $autoloader;

$app = \App\Core\Application::getInstance(BASE_PATH);
\App\Core\View::shareMany(['appName'=>env('APP_NAME','Support Portal'),'appUrl'=>env('APP_URL',''),'appDebug'=>env('APP_DEBUG',false)]);
$app->run();
