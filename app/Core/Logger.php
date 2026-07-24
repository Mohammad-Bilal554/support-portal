<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Logger
 *
 * PSR-3 inspired file logger.
 * Levels: emergency, alert, critical, error, warning, notice, info, debug
 * Daily log rotation: app-2024-01-15.log
 */
class Logger
{
    private static ?Logger $instance = null;

    private string $logPath;
    private string $minLevel;
    private bool   $enabled;

    /** Level priority map */
    private const LEVELS = [
        'debug'     => 0,
        'info'      => 1,
        'notice'    => 2,
        'warning'   => 3,
        'error'     => 4,
        'critical'  => 5,
        'alert'     => 6,
        'emergency' => 7,
    ];

    private function __construct()
    {
        $basePath      = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $logFile       = env('LOG_PATH', 'storage/logs/app.log');
        $this->logPath = $basePath . '/' . ltrim($logFile, '/');
        $this->minLevel = env('LOG_LEVEL', 'debug');
        $this->enabled  = env('LOG_CHANNEL', 'file') !== 'none';

        // Ensure log directory exists
        $logDir = dirname($this->logPath);
        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    // ----------------------------------------------------------------
    // PSR-3 level methods
    // ----------------------------------------------------------------

    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    // ----------------------------------------------------------------
    // Core logging
    // ----------------------------------------------------------------

    public function log(string $level, string $message, array $context = []): void
    {
        if (! $this->enabled) {
            return;
        }

        // Check min level threshold
        $levelPriority    = static::LEVELS[$level]         ?? 0;
        $minLevelPriority = static::LEVELS[$this->minLevel] ?? 0;

        if ($levelPriority < $minLevelPriority) {
            return;
        }

        $message = $this->interpolate($message, $context);
        $line    = $this->formatLine($level, $message, $context);

        // Daily log rotation
        $logFile = $this->getDailyLogPath();

        // Write with file lock to prevent corruption under concurrency
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /** PSR-3 context interpolation: {key} → value */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (! is_array($val) && (! is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        return strtr($message, $replace);
    }

    private function formatLine(string $level, string $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        $pid       = getmypid();

        $line = "[{$timestamp}] [{$levelUpper}] [pid:{$pid}] {$message}";

        if (! empty($context)) {
            $line .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return $line . PHP_EOL;
    }

    private function getDailyLogPath(): string
    {
        $dir      = dirname($this->logPath);
        $basename = pathinfo($this->logPath, PATHINFO_FILENAME);
        $ext      = pathinfo($this->logPath, PATHINFO_EXTENSION);
        $date     = date('Y-m-d');

        return $dir . '/' . $basename . '-' . $date . '.' . $ext;
    }

    // ----------------------------------------------------------------
    // Log reading (for admin log viewer)
    // ----------------------------------------------------------------

    public function readLogs(int $lines = 100, string $date = null): array
    {
        $date    = $date ?? date('Y-m-d');
        $logFile = $this->getDailyLogPath();

        if (! file_exists($logFile)) {
            return [];
        }

        $allLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (! $allLines) {
            return [];
        }

        $lastLines = array_slice($allLines, -$lines);

        return array_map(function (string $line) {
            preg_match('/^\[(.+?)\] \[(.+?)\] \[pid:(\d+)\] (.+?)( \| Context: (.+))?$/', $line, $m);
            return [
                'timestamp' => $m[1]  ?? '',
                'level'     => $m[2]  ?? '',
                'pid'       => $m[3]  ?? '',
                'message'   => $m[4]  ?? $line,
                'context'   => isset($m[6]) ? json_decode($m[6], true) : null,
                'raw'       => $line,
            ];
        }, $lastLines);
    }

    public function getLogFiles(): array
    {
        $dir   = dirname($this->logPath);
        $files = glob($dir . '/*.log');
        rsort($files);
        return $files;
    }
}
