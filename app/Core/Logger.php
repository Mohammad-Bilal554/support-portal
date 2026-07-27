<?php
declare(strict_types=1);
namespace App\Core;

class Logger {
    private static ?Logger $instance = null;
    private string $logPath;
    private const LEVELS = ['debug'=>0,'info'=>1,'notice'=>2,'warning'=>3,'error'=>4,'critical'=>5,'alert'=>6,'emergency'=>7];

    private function __construct() {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $this->logPath = $basePath . '/' . ltrim(env('LOG_PATH','storage/logs/app.log'),'/');
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }
    public static function getInstance(): static {
        if (!static::$instance) static::$instance = new static();
        return static::$instance;
    }
    public function emergency(string $m, array $c=[]): void { $this->log('emergency',$m,$c); }
    public function alert(string $m, array $c=[]): void     { $this->log('alert',$m,$c); }
    public function critical(string $m, array $c=[]): void  { $this->log('critical',$m,$c); }
    public function error(string $m, array $c=[]): void     { $this->log('error',$m,$c); }
    public function warning(string $m, array $c=[]): void   { $this->log('warning',$m,$c); }
    public function notice(string $m, array $c=[]): void    { $this->log('notice',$m,$c); }
    public function info(string $m, array $c=[]): void      { $this->log('info',$m,$c); }
    public function debug(string $m, array $c=[]): void     { $this->log('debug',$m,$c); }

    public function log(string $level, string $message, array $context=[]): void {
        $dir   = dirname($this->logPath);
        $base  = pathinfo($this->logPath, PATHINFO_FILENAME);
        $ext   = pathinfo($this->logPath, PATHINFO_EXTENSION);
        $file  = $dir.'/'.$base.'-'.date('Y-m-d').'.'.$ext;
        $line  = sprintf("[%s] [%s] %s%s\n", date('Y-m-d H:i:s'), strtoupper($level), $message,
            !empty($context) ? ' | '.json_encode($context) : '');
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
