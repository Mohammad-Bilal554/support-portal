<?php

declare(strict_types=1);

namespace App\Core;

/**
 * View
 *
 * Template renderer with:
 * - Layout support (wrap view in a layout file)
 * - Section/yield blocks (content areas)
 * - Shared data available to all views
 * - Escape helpers
 * - CSRF token injection
 */
class View
{
    private static array $shared   = [];
    private static array $sections = [];
    private static array $stack    = [];  // for nested sections

    private string $viewPath;
    private string $layout   = '';
    private array  $data     = [];

    // ----------------------------------------------------------------
    // Factory
    // ----------------------------------------------------------------

    public static function make(string $view, array $data = []): static
    {
        return new static($view, $data);
    }

    public function __construct(string $view, array $data = [])
    {
        $this->viewPath = $view;
        $this->data     = array_merge(static::$shared, $data);
    }

    // ----------------------------------------------------------------
    // Shared data (available in every view)
    // ----------------------------------------------------------------

    public static function share(string $key, mixed $value): void
    {
        static::$shared[$key] = $value;
    }

    public static function shareMany(array $data): void
    {
        static::$shared = array_merge(static::$shared, $data);
    }

    // ----------------------------------------------------------------
    // Layout
    // ----------------------------------------------------------------

    public function layout(string $layout): static
    {
        $this->layout = $layout;
        return $this;
    }

    public function with(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function withMany(array $data): static
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    // ----------------------------------------------------------------
    // Render
    // ----------------------------------------------------------------

    public function render(): string
    {
        $content = $this->renderFile($this->resolvePath($this->viewPath), $this->data);

        if ($this->layout) {
            $layoutPath            = $this->resolvePath($this->layout);
            $this->data['content'] = $content;
            $content = $this->renderFile($layoutPath, $this->data);
        }

        return $content;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    // ----------------------------------------------------------------
    // Section / Yield (template inheritance)
    // ----------------------------------------------------------------

    public static function startSection(string $name): void
    {
        static::$stack[] = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        $name = array_pop(static::$stack);

        if ($name === null) {
            throw new \RuntimeException('No open section to close.');
        }

        static::$sections[$name] = ob_get_clean();
    }

    public static function yield(string $name, string $default = ''): string
    {
        return static::$sections[$name] ?? $default;
    }

    public static function hasSection(string $name): bool
    {
        return isset(static::$sections[$name]);
    }

    // ----------------------------------------------------------------
    // Render a PHP template file
    // ----------------------------------------------------------------

    private function renderFile(string $filePath, array $data): string
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException("View file not found: [{$filePath}]");
        }

        // Extract data as local variables for the template
        extract($data, EXTR_SKIP);

        // Make view helpers available
        $view = $this;

        ob_start();
        include $filePath;
        return ob_get_clean();
    }

    // ----------------------------------------------------------------
    // Path resolution
    // ----------------------------------------------------------------

    private function resolvePath(string $view): string
    {
        // Convert dot notation: 'admin.dashboard' → 'admin/dashboard'
        $relative = str_replace('.', '/', $view) . '.php';
        $base     = Application::getInstance()->path('resources/views');

        return $base . '/' . $relative;
    }

    // ----------------------------------------------------------------
    // Escape helpers (available inside templates as $view->e())
    // ----------------------------------------------------------------

    public function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function raw(string $html): string
    {
        return $html;   // No escaping — use deliberately
    }

    // ----------------------------------------------------------------
    // Template helper shortcuts (callable from inside .php templates)
    // ----------------------------------------------------------------

    public function csrf(): string
    {
        $token = Csrf::getToken();
        return '<input type="hidden" name="_csrf_token" value="' . $this->e($token) . '">';
    }

    public function csrfToken(): string
    {
        return Csrf::getToken();
    }

    public function old(string $key, mixed $default = ''): string
    {
        $old = Session::getInstance()->getFlash('old') ?? [];
        return $this->e($old[$key] ?? $default);
    }

    public function hasError(string $key): bool
    {
        $errors = Session::getInstance()->getFlash('errors') ?? [];
        return isset($errors[$key]);
    }

    public function error(string $key): string
    {
        $errors = Session::getInstance()->getFlash('errors') ?? [];
        return $this->e($errors[$key][0] ?? '');
    }

    public function asset(string $path): string
    {
        $base = rtrim(env('APP_URL', ''), '/');
        return $base . '/assets/' . ltrim($path, '/');
    }

    public function url(string $path = ''): string
    {
        $base = rtrim(env('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }

    public function route(string $name, array $params = []): string
    {
        return Application::getInstance()->make(Router::class)->route($name, $params);
    }
}
