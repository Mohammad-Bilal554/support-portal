<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Application
 *
 * The heart of the framework. Bootstraps all services,
 * loads configuration, and dispatches the HTTP request
 * through the router.
 */
class Application
{
    /** Singleton instance */
    private static ?Application $instance = null;

    /** Registered service bindings */
    private array $bindings = [];

    /** Resolved singleton instances */
    private array $instances = [];

    /** Application base path */
    public readonly string $basePath;

    /** Boot time (for performance tracking) */
    public readonly float $startTime;

    // ----------------------------------------------------------------
    // Bootstrap
    // ----------------------------------------------------------------

    private function __construct(string $basePath)
    {
        $this->basePath  = rtrim($basePath, '/\\');
        $this->startTime = microtime(true);

        $this->loadEnvironment();
        $this->configureErrorHandling();
        $this->configureSession();
        $this->registerCoreBindings();
    }

    /** Get or create the application singleton */
    public static function getInstance(string $basePath = ''): static
    {
        if (static::$instance === null) {
            if (empty($basePath)) {
                throw new \RuntimeException('Base path required on first instantiation.');
            }
            static::$instance = new static($basePath);
        }

        return static::$instance;
    }

    // ----------------------------------------------------------------
    // Environment
    // ----------------------------------------------------------------

    private function loadEnvironment(): void
    {
        $envFile = $this->basePath . '/.env';

        if (! file_exists($envFile)) {
            // Silently continue — defaults will be used
            return;
        }

        // Use vlucas/phpdotenv if available; otherwise manual parse
        if (class_exists(\Dotenv\Dotenv::class)) {
            $dotenv = \Dotenv\Dotenv::createImmutable($this->basePath);
            $dotenv->safeLoad();
        } else {
            $this->parseEnvFile($envFile);
        }
    }

    private function parseEnvFile(string $path): void
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments
            if (str_starts_with($line, '#') || $line === '') {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key   = trim($key);
                $value = trim($value);

                // Strip surrounding quotes
                $value = trim($value, '"\'');

                if (! array_key_exists($key, $_ENV)) {
                    $_ENV[$key]    = $value;
                    $_SERVER[$key] = $value;
                    putenv("{$key}={$value}");
                }
            }
        }
    }

    // ----------------------------------------------------------------
    // Error Handling
    // ----------------------------------------------------------------

    private function configureErrorHandling(): void
    {
        $debug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);

        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '0');
        }

        // Register global error/exception handlers
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(
        int    $errno,
        string $errstr,
        string $errfile = '',
        int    $errline = 0
    ): bool {
        if (! (error_reporting() & $errno)) {
            return false;
        }

        $logger = Logger::getInstance();
        $logger->error("PHP Error [{$errno}]: {$errstr} in {$errfile} on line {$errline}");

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public function handleException(\Throwable $e): void
    {
        $logger = Logger::getInstance();
        $logger->error(sprintf(
            'Uncaught %s: %s in %s:%d',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
        $logger->error('Stack trace: ' . $e->getTraceAsString());

        $debug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);

        http_response_code(500);

        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $debug ? $e->getMessage() : 'Internal Server Error',
                'trace'   => $debug ? $e->getTrace() : null,
            ]);
            return;
        }

        if ($debug) {
            echo $this->renderDebugPage($e);
        } else {
            echo $this->renderErrorPage(500, 'Internal Server Error');
        }
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handleException(new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            ));
        }
    }

    // ----------------------------------------------------------------
    // Session
    // ----------------------------------------------------------------

    private function configureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && ! headers_sent()) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_samesite', 'Lax');

            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', '1');
            }

            $sessionName     = env('SESSION_NAME', 'support_portal_session');
            $sessionLifetime = (int) env('SESSION_LIFETIME', 120);

            session_name($sessionName);
            session_set_cookie_params([
                'lifetime' => $sessionLifetime * 60,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            session_start();
        }
    }

    // ----------------------------------------------------------------
    // Service Container (Simple DI)
    // ----------------------------------------------------------------

    private function registerCoreBindings(): void
    {
        $this->singleton(Database::class, fn() => new Database());
        $this->singleton(Router::class,   fn() => new Router());
        $this->singleton(Request::class,  fn() => new Request());
        $this->singleton(Response::class, fn() => new Response());
        $this->singleton(Session::class,  fn() => new Session());
    }

    /** Bind a factory (new instance each time) */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /** Bind a singleton (resolved once, reused) */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = function () use ($abstract, $factory) {
            if (! isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($this);
            }
            return $this->instances[$abstract];
        };
    }

    /** Resolve a binding */
    public function make(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])();
        }

        // Auto-resolve class
        if (class_exists($abstract)) {
            return new $abstract();
        }

        throw new \RuntimeException("Cannot resolve [{$abstract}] from the container.");
    }

    // ----------------------------------------------------------------
    // Run
    // ----------------------------------------------------------------

    public function run(): void
    {
        /** @var Router $router */
        $router = $this->make(Router::class);

        // Load route definitions
        require_once $this->basePath . '/config/routes.php';

        /** @var Request $request */
        $request = $this->make(Request::class);

        $router->dispatch($request);
    }

    // ----------------------------------------------------------------
    // Path helpers
    // ----------------------------------------------------------------

    public function path(string $relative = ''): string
    {
        return $this->basePath . ($relative ? DIRECTORY_SEPARATOR . ltrim($relative, '/\\') : '');
    }

    public function storagePath(string $relative = ''): string
    {
        return $this->path('storage' . ($relative ? '/' . $relative : ''));
    }

    public function publicPath(string $relative = ''): string
    {
        return $this->path('public' . ($relative ? '/' . $relative : ''));
    }

    // ----------------------------------------------------------------
    // Utilities
    // ----------------------------------------------------------------

    private function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_starts_with(parse_url($uri, PHP_URL_PATH) ?? '', '/api/');
    }

    private function renderDebugPage(\Throwable $e): string
    {
        $class   = htmlspecialchars(get_class($e));
        $message = htmlspecialchars($e->getMessage());
        $file    = htmlspecialchars($e->getFile());
        $line    = $e->getLine();
        $trace   = htmlspecialchars($e->getTraceAsString());

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><title>Application Error</title>
        <style>
            body { font-family: monospace; background: #1a1a2e; color: #e0e0e0; padding: 2rem; }
            h1   { color: #e94560; }
            .box { background: #16213e; border-left: 4px solid #e94560; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
            pre  { overflow-x: auto; white-space: pre-wrap; }
        </style>
        </head>
        <body>
            <h1>⚠ {$class}</h1>
            <div class="box"><strong>Message:</strong> {$message}</div>
            <div class="box"><strong>File:</strong> {$file} <strong>Line:</strong> {$line}</div>
            <div class="box"><strong>Stack Trace:</strong><pre>{$trace}</pre></div>
        </body>
        </html>
        HTML;
    }

    private function renderErrorPage(int $code, string $message): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><title>{$code} | {$message}</title></head>
        <body style="font-family:sans-serif;text-align:center;padding:4rem;">
            <h1 style="font-size:5rem;color:#dc3545;">{$code}</h1>
            <p>{$message}</p>
            <a href="/">Go Home</a>
        </body>
        </html>
        HTML;
    }
}
