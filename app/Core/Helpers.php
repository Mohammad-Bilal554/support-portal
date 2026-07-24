<?php

declare(strict_types=1);

/**
 * Global Helper Functions
 *
 * Loaded via composer.json autoload.files.
 * These are procedural shortcuts to framework classes.
 */

// ----------------------------------------------------------------
// Environment
// ----------------------------------------------------------------

if (! function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}

// ----------------------------------------------------------------
// Application
// ----------------------------------------------------------------

if (! function_exists('app')) {
    function app(string $abstract = null): mixed
    {
        $application = \App\Core\Application::getInstance();

        if ($abstract === null) {
            return $application;
        }

        return $application->make($abstract);
    }
}

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return \App\Core\Application::getInstance()->path($path);
    }
}

if (! function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return \App\Core\Application::getInstance()->storagePath($path);
    }
}

if (! function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return \App\Core\Application::getInstance()->publicPath($path);
    }
}

// ----------------------------------------------------------------
// URL & Routing
// ----------------------------------------------------------------

if (! function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(env('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (! function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        return app(\App\Core\Router::class)->route($name, $params);
    }
}

if (! function_exists('current_url')) {
    function current_url(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
    }
}

if (! function_exists('redirect')) {
    function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}

if (! function_exists('redirect_back')) {
    function redirect_back(): never
    {
        $url = $_SERVER['HTTP_REFERER'] ?? url('/');
        header('Location: ' . $url);
        exit;
    }
}

if (! function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

// ----------------------------------------------------------------
// View
// ----------------------------------------------------------------

if (! function_exists('view')) {
    function view(string $name, array $data = []): string
    {
        return \App\Core\View::make($name, $data)->render();
    }
}

// ----------------------------------------------------------------
// Session
// ----------------------------------------------------------------

if (! function_exists('session')) {
    function session(string $key = null, mixed $default = null): mixed
    {
        $session = \App\Core\Session::getInstance();

        if ($key === null) {
            return $session;
        }

        return $session->get($key, $default);
    }
}

if (! function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        \App\Core\Session::getInstance()->setFlash($type, $message);
    }
}

if (! function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = \App\Core\Session::getInstance()->getFlash('old') ?? [];
        return $old[$key] ?? $default;
    }
}

// ----------------------------------------------------------------
// CSRF
// ----------------------------------------------------------------

if (! function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Core\Csrf::field();
    }
}

if (! function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\Csrf::getToken();
    }
}

// ----------------------------------------------------------------
// Auth
// ----------------------------------------------------------------

if (! function_exists('auth')) {
    function auth(): ?\App\Core\Session
    {
        return \App\Core\Session::getInstance();
    }
}

if (! function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return \App\Core\Session::getInstance()->getUser();
    }
}

if (! function_exists('auth_id')) {
    function auth_id(): ?int
    {
        return \App\Core\Session::getInstance()->getUserId();
    }
}

if (! function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return \App\Core\Session::getInstance()->isLoggedIn();
    }
}

// ----------------------------------------------------------------
// Security & Encoding
// ----------------------------------------------------------------

if (! function_exists('e')) {
    /** HTML entity encode */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (! function_exists('hash_password')) {
    function hash_password(string $password): string
    {
        $cost = (int) env('BCRYPT_ROUNDS', 12);
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }
}

if (! function_exists('verify_password')) {
    function verify_password(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}

if (! function_exists('generate_token')) {
    function generate_token(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }
}

// ----------------------------------------------------------------
// String helpers
// ----------------------------------------------------------------

if (! function_exists('str_limit')) {
    function str_limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        return mb_substr($value, 0, $limit) . $end;
    }
}

if (! function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = preg_replace('/[^\\pL\d]+/u', '-', $text);
        $text = trim($text, '-');
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        $text = strtolower($text);
        $text = preg_replace('/[^-\w]+/', '', $text);
        return $text;
    }
}

if (! function_exists('ucfirst_words')) {
    function ucfirst_words(string $text): string
    {
        return ucwords(strtolower($text));
    }
}

if (! function_exists('mask_email')) {
    function mask_email(string $email): string
    {
        [$user, $domain] = explode('@', $email);
        $masked = substr($user, 0, 2) . str_repeat('*', max(strlen($user) - 2, 0));
        return $masked . '@' . $domain;
    }
}

// ----------------------------------------------------------------
// Date & Time
// ----------------------------------------------------------------

if (! function_exists('time_ago')) {
    function time_ago(string $datetime): string
    {
        $time = time() - strtotime($datetime);

        if ($time < 60)      return $time . ' sec ago';
        if ($time < 3600)    return floor($time / 60) . ' min ago';
        if ($time < 86400)   return floor($time / 3600) . ' hr ago';
        if ($time < 604800)  return floor($time / 86400) . ' days ago';
        if ($time < 2592000) return floor($time / 604800) . ' weeks ago';
        if ($time < 31536000) return floor($time / 2592000) . ' months ago';
        return floor($time / 31536000) . ' years ago';
    }
}

if (! function_exists('format_date')) {
    function format_date(string $datetime, string $format = 'd M Y'): string
    {
        if (! $datetime) return '';
        return date($format, strtotime($datetime));
    }
}

if (! function_exists('format_datetime')) {
    function format_datetime(string $datetime, string $format = 'd M Y, H:i'): string
    {
        return format_date($datetime, $format);
    }
}

// ----------------------------------------------------------------
// Numbers & Files
// ----------------------------------------------------------------

if (! function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        return round($bytes / 1024 ** $pow, $precision) . ' ' . $units[$pow];
    }
}

if (! function_exists('format_number')) {
    function format_number(int|float $number, int $decimals = 0): string
    {
        return number_format($number, $decimals);
    }
}

// ----------------------------------------------------------------
// Array helpers
// ----------------------------------------------------------------

if (! function_exists('array_get')) {
    function array_get(array $array, string $key, mixed $default = null): mixed
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        // Dot notation
        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }
}

if (! function_exists('array_pluck')) {
    function array_pluck(array $array, string $key): array
    {
        return array_column($array, $key);
    }
}

// ----------------------------------------------------------------
// JSON
// ----------------------------------------------------------------

if (! function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// ----------------------------------------------------------------
// Debug
// ----------------------------------------------------------------

if (! function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        http_response_code(200);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<style>body{background:#1a1a2e;color:#e0e0e0;font-family:monospace;padding:2rem;}
              pre{background:#16213e;padding:1rem;border-radius:4px;overflow:auto;}
              h3{color:#e94560;}</style>';

        foreach ($vars as $var) {
            echo '<h3>' . gettype($var) . '</h3>';
            echo '<pre>' . htmlspecialchars(print_r($var, true)) . '</pre>';
        }

        exit;
    }
}

if (! function_exists('dump')) {
    function dump(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre style="background:#1a1a2e;color:#e0e0e0;padding:1rem;margin:0.5rem;border-radius:4px;font-family:monospace;">';
            echo htmlspecialchars(print_r($var, true));
            echo '</pre>';
        }
    }
}

// ----------------------------------------------------------------
// Ticket specific helpers
// ----------------------------------------------------------------

if (! function_exists('ticket_status_badge')) {
    function ticket_status_badge(string $status): string
    {
        $map = [
            'open'                => 'bg-danger',
            'assigned'            => 'bg-warning text-dark',
            'in_progress'         => 'bg-primary',
            'waiting_for_client'  => 'bg-info text-dark',
            'resolved'            => 'bg-success',
            'closed'              => 'bg-secondary',
        ];
        $class = $map[$status] ?? 'bg-secondary';
        $label = ucwords(str_replace('_', ' ', $status));
        return "<span class=\"badge {$class}\">{$label}</span>";
    }
}

if (! function_exists('ticket_priority_badge')) {
    function ticket_priority_badge(string $priority): string
    {
        $map = [
            'low'      => ['class' => 'bg-success',  'icon' => '↓'],
            'medium'   => ['class' => 'bg-warning text-dark', 'icon' => '→'],
            'high'     => ['class' => 'bg-danger',   'icon' => '↑'],
            'critical' => ['class' => 'bg-dark',     'icon' => '‼'],
        ];
        $item  = $map[$priority] ?? ['class' => 'bg-secondary', 'icon' => ''];
        $label = ucfirst($priority);
        return "<span class=\"badge {$item['class']}\">{$item['icon']} {$label}</span>";
    }
}

if (! function_exists('generate_ticket_number')) {
    function generate_ticket_number(): string
    {
        return 'TKT-' . date('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }
}

// ----------------------------------------------------------------
// Pagination HTML
// ----------------------------------------------------------------

if (! function_exists('render_pagination')) {
    function render_pagination(array $paginator, string $baseUrl): string
    {
        if ($paginator['last_page'] <= 1) {
            return '';
        }

        $current  = $paginator['current_page'];
        $last     = $paginator['last_page'];
        $html     = '<nav><ul class="pagination pagination-sm mb-0">';

        // Previous
        $html .= $current <= 1
            ? '<li class="page-item disabled"><span class="page-link">‹</span></li>'
            : '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($current - 1) . '">‹</a></li>';

        // Pages
        for ($i = max(1, $current - 2); $i <= min($last, $current + 2); $i++) {
            $active  = $i === $current ? ' active' : '';
            $html   .= "<li class=\"page-item{$active}\"><a class=\"page-link\" href=\"{$baseUrl}?page={$i}\">{$i}</a></li>";
        }

        // Next
        $html .= $current >= $last
            ? '<li class="page-item disabled"><span class="page-link">›</span></li>'
            : '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($current + 1) . '">›</a></li>';

        $html .= '</ul></nav>';
        return $html;
    }
}
