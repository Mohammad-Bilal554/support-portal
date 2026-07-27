<?php
declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false) return $default;
        return match(strtolower((string)$value)) {
            'true','(true)' => true, 'false','(false)' => false,
            'null','(null)' => null, default => $value,
        };
    }
}
if (!function_exists('app')) {
    function app(string $abstract = null): mixed {
        $a = \App\Core\Application::getInstance();
        return $abstract ? $a->make($abstract) : $a;
    }
}
if (!function_exists('base_path')) {
    function base_path(string $path = ''): string {
        return \App\Core\Application::getInstance()->path($path);
    }
}
if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string {
        return \App\Core\Application::getInstance()->storagePath($path);
    }
}
if (!function_exists('public_path')) {
    function public_path(string $path = ''): string {
        return \App\Core\Application::getInstance()->publicPath($path);
    }
}
if (!function_exists('url')) {
    function url(string $path = ''): string {
        $base = rtrim(env('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}
if (!function_exists('route')) {
    function route(string $name, array $params = []): string {
        return app(\App\Core\Router::class)->route($name, $params);
    }
}
if (!function_exists('current_url')) {
    function current_url(): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
    }
}
if (!function_exists('redirect')) {
    function redirect(string $url): never { header('Location: ' . $url); exit; }
}
if (!function_exists('redirect_back')) {
    function redirect_back(): never {
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('/'))); exit;
    }
}
if (!function_exists('asset')) {
    function asset(string $path): string { return url('assets/' . ltrim($path, '/')); }
}
if (!function_exists('view')) {
    function view(string $name, array $data = []): string {
        return \App\Core\View::make($name, $data)->render();
    }
}
if (!function_exists('session')) {
    function session(string $key = null, mixed $default = null): mixed {
        $s = \App\Core\Session::getInstance();
        return $key === null ? $s : $s->get($key, $default);
    }
}
if (!function_exists('flash')) {
    function flash(string $type, string $message): void {
        \App\Core\Session::getInstance()->setFlash($type, $message);
    }
}
if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed {
        $old = \App\Core\Session::getInstance()->getFlash('old') ?? [];
        return $old[$key] ?? $default;
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): string { return \App\Core\Csrf::field(); }
}
if (!function_exists('csrf_token')) {
    function csrf_token(): string { return \App\Core\Csrf::getToken(); }
}
if (!function_exists('auth_user')) {
    function auth_user(): ?array { return \App\Core\Session::getInstance()->getUser(); }
}
if (!function_exists('auth_id')) {
    function auth_id(): ?int { return \App\Core\Session::getInstance()->getUserId(); }
}
if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool { return \App\Core\Session::getInstance()->isLoggedIn(); }
}
if (!function_exists('e')) {
    function e(mixed $value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
if (!function_exists('hash_password')) {
    function hash_password(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => (int)env('BCRYPT_ROUNDS', 12)]);
    }
}
if (!function_exists('verify_password')) {
    function verify_password(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}
if (!function_exists('generate_token')) {
    function generate_token(int $bytes = 32): string { return bin2hex(random_bytes($bytes)); }
}
if (!function_exists('str_limit')) {
    function str_limit(string $value, int $limit = 100, string $end = '...'): string {
        return mb_strlen($value) <= $limit ? $value : mb_substr($value, 0, $limit) . $end;
    }
}
if (!function_exists('time_ago')) {
    function time_ago(string $datetime): string {
        $time = time() - strtotime($datetime);
        if ($time < 60)      return $time . ' sec ago';
        if ($time < 3600)    return floor($time/60) . ' min ago';
        if ($time < 86400)   return floor($time/3600) . ' hr ago';
        if ($time < 604800)  return floor($time/86400) . ' days ago';
        return date('d M Y', strtotime($datetime));
    }
}
if (!function_exists('format_date')) {
    function format_date(string $datetime, string $format = 'd M Y'): string {
        return $datetime ? date($format, strtotime($datetime)) : '';
    }
}
if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $precision = 2): string {
        $units = ['B','KB','MB','GB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        return round($bytes / 1024 ** $pow, $precision) . ' ' . $units[$pow];
    }
}
if (!function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): never {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
if (!function_exists('dd')) {
    function dd(mixed ...$vars): never {
        foreach ($vars as $v) { echo '<pre style="background:#1a1a2e;color:#e0e0e0;padding:1rem;font-family:monospace;">'; print_r($v); echo '</pre>'; }
        exit;
    }
}
if (!function_exists('ticket_status_badge')) {
    function ticket_status_badge(string $status): string {
        $map = ['open'=>'bg-danger','assigned'=>'bg-warning text-dark','in_progress'=>'bg-primary','waiting_for_client'=>'bg-info text-dark','resolved'=>'bg-success','closed'=>'bg-secondary'];
        $class = $map[$status] ?? 'bg-secondary';
        return '<span class="badge '.$class.'">'.ucwords(str_replace('_',' ',$status)).'</span>';
    }
}
if (!function_exists('ticket_priority_badge')) {
    function ticket_priority_badge(string $priority): string {
        $map = ['low'=>'bg-success','medium'=>'bg-warning text-dark','high'=>'bg-danger','critical'=>'bg-dark'];
        $class = $map[$priority] ?? 'bg-secondary';
        return '<span class="badge '.$class.'">'.ucfirst($priority).'</span>';
    }
}
if (!function_exists('generate_ticket_number')) {
    function generate_ticket_number(): string {
        return 'TKT-'.date('Y').'-'.str_pad((string)random_int(1,99999),5,'0',STR_PAD_LEFT);
    }
}
if (!function_exists('render_pagination')) {
    function render_pagination(array $p, string $baseUrl): string {
        if ($p['last_page'] <= 1) return '';
        $cur = $p['current_page']; $last = $p['last_page'];
        $html = '<nav><ul class="pagination pagination-sm mb-0">';
        $html .= $cur <= 1 ? '<li class="page-item disabled"><span class="page-link">‹</span></li>' : '<li class="page-item"><a class="page-link" href="'.$baseUrl.'?page='.($cur-1).'">‹</a></li>';
        for ($i = max(1,$cur-2); $i <= min($last,$cur+2); $i++) {
            $a = $i===$cur?' active':'';
            $html .= "<li class=\"page-item{$a}\"><a class=\"page-link\" href=\"{$baseUrl}?page={$i}\">{$i}</a></li>";
        }
        $html .= $cur >= $last ? '<li class="page-item disabled"><span class="page-link">›</span></li>' : '<li class="page-item"><a class="page-link" href="'.$baseUrl.'?page='.($cur+1).'">›</a></li>';
        return $html . '</ul></nav>';
    }
}
