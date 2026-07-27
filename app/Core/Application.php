<?php
declare(strict_types=1);
namespace App\Core;

class Application {
    private static ?Application $instance = null;
    private array $bindings  = [];
    private array $instances = [];
    public readonly string $basePath;
    public readonly float  $startTime;

    private function __construct(string $basePath) {
        $this->basePath  = rtrim($basePath,'/\\');
        $this->startTime = microtime(true);
        $this->loadEnvironment();
        $this->configureErrorHandling();
        $this->configureSession();
        $this->registerCoreBindings();
    }
    public static function getInstance(string $basePath=''): static {
        if (!static::$instance) {
            if (!$basePath) throw new \RuntimeException('Base path required on first instantiation.');
            static::$instance = new static($basePath);
        }
        return static::$instance;
    }
    private function loadEnvironment(): void {
        $envFile=$this->basePath.'/.env';
        if (!file_exists($envFile)) return;
        if (class_exists(\Dotenv\Dotenv::class)) {
            \Dotenv\Dotenv::createImmutable($this->basePath)->safeLoad();
        } else {
            foreach (file($envFile,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
                $line=trim($line);
                if (str_starts_with($line,'#')||!str_contains($line,'=')) continue;
                [$k,$v]=explode('=',$line,2);
                $k=trim($k); $v=trim(trim($v),'"\'');
                if (!array_key_exists($k,$_ENV)) { $_ENV[$k]=$v; $_SERVER[$k]=$v; putenv("{$k}={$v}"); }
            }
        }
    }
    private function configureErrorHandling(): void {
        $debug=filter_var(env('APP_DEBUG',false),FILTER_VALIDATE_BOOLEAN);
        error_reporting(E_ALL);
        ini_set('display_errors',$debug?'1':'0');
        set_error_handler([$this,'handleError']);
        set_exception_handler([$this,'handleException']);
        register_shutdown_function([$this,'handleShutdown']);
    }
    public function handleError(int $errno, string $errstr, string $errfile='', int $errline=0): bool {
        if (!(error_reporting()&$errno)) return false;
        Logger::getInstance()->error("PHP Error [{$errno}]: {$errstr} in {$errfile}:{$errline}");
        throw new \ErrorException($errstr,0,$errno,$errfile,$errline);
    }
    public function handleException(\Throwable $e): void {
        Logger::getInstance()->error(get_class($e).': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
        $debug=filter_var(env('APP_DEBUG',false),FILTER_VALIDATE_BOOLEAN);
        http_response_code(500);
        if ($debug) {
            echo '<pre style="background:#1a1a2e;color:#e0e0e0;padding:2rem;font-family:monospace;">';
            echo '<h2 style="color:#e94560;">'.htmlspecialchars(get_class($e)).'</h2>';
            echo '<p>'.htmlspecialchars($e->getMessage()).'</p>';
            echo '<p>'.htmlspecialchars($e->getFile()).':'.$e->getLine().'</p>';
            echo htmlspecialchars($e->getTraceAsString()).'</pre>';
        } else {
            $p=Application::getInstance()->path('resources/views/errors/500.php');
            file_exists($p)?include $p:print('<h1>500 Server Error</h1>');
        }
    }
    public function handleShutdown(): void {
        $e=error_get_last();
        if ($e&&in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR]))
            $this->handleException(new \ErrorException($e['message'],0,$e['type'],$e['file'],$e['line']));
    }
    private function configureSession(): void {
        if (session_status()===PHP_SESSION_NONE&&!headers_sent()) {
            ini_set('session.cookie_httponly','1');
            ini_set('session.use_strict_mode','1');
            ini_set('session.cookie_samesite','Lax');
            session_name(env('SESSION_NAME','support_portal_session'));
            session_set_cookie_params(['lifetime'=>(int)env('SESSION_LIFETIME',120)*60,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
            session_start();
        }
    }
    private function registerCoreBindings(): void {
        $this->singleton(Database::class, fn()=>new Database());
        $this->singleton(Router::class,   fn()=>new Router());
        $this->singleton(Request::class,  fn()=>new Request());
        $this->singleton(Response::class, fn()=>new Response());
        $this->singleton(Session::class,  fn()=>new Session());
    }
    public function bind(string $abstract, callable $factory): void { $this->bindings[$abstract]=$factory; }
    public function singleton(string $abstract, callable $factory): void {
        $this->bindings[$abstract]=function() use($abstract,$factory) {
            if (!isset($this->instances[$abstract])) $this->instances[$abstract]=$factory($this);
            return $this->instances[$abstract];
        };
    }
    public function make(string $abstract): mixed {
        if (isset($this->bindings[$abstract])) return ($this->bindings[$abstract])();
        if (class_exists($abstract)) return new $abstract();
        throw new \RuntimeException("Cannot resolve [{$abstract}].");
    }
    public function run(): void {
        $router=$this->make(Router::class);
        require_once $this->basePath.'/config/routes.php';
        $router->dispatch($this->make(Request::class));
    }
    public function path(string $relative=''): string { return $this->basePath.($relative?DIRECTORY_SEPARATOR.ltrim($relative,'/\\'):''); }
    public function storagePath(string $relative=''): string { return $this->path('storage'.($relative?'/'.$relative:'')); }
    public function publicPath(string $relative=''): string  { return $this->path('public'.($relative?'/'.$relative:'')); }
}
