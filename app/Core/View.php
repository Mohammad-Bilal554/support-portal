<?php
declare(strict_types=1);
namespace App\Core;

class View {
    private static array $shared   = [];
    private static array $sections = [];
    private static array $stack    = [];
    private string $viewPath;
    private string $layout = '';
    private array  $data   = [];

    public static function make(string $view, array $data=[]): static { return new static($view,$data); }
    public function __construct(string $view, array $data=[]) { $this->viewPath=$view; $this->data=array_merge(static::$shared,$data); }
    public static function share(string $key, mixed $value): void { static::$shared[$key]=$value; }
    public static function shareMany(array $data): void { static::$shared=array_merge(static::$shared,$data); }
    public function layout(string $layout): static { $this->layout=$layout; return $this; }
    public function with(string $key, mixed $value): static { $this->data[$key]=$value; return $this; }
    public function withMany(array $data): static { $this->data=array_merge($this->data,$data); return $this; }

    public function render(): string {
        $content = $this->renderFile($this->resolvePath($this->viewPath), $this->data);
        if ($this->layout) {
            $this->data['content'] = $content;
            $content = $this->renderFile($this->resolvePath($this->layout), $this->data);
        }
        return $content;
    }
    public function __toString(): string { return $this->render(); }

    public static function startSection(string $name): void { static::$stack[]=$name; ob_start(); }
    public static function endSection(): void {
        $name=array_pop(static::$stack);
        if ($name===null) throw new \RuntimeException('No open section to close.');
        static::$sections[$name]=ob_get_clean();
    }
    public static function yield(string $name, string $default=''): string { return static::$sections[$name]??$default; }
    public static function hasSection(string $name): bool { return isset(static::$sections[$name]); }

    private function renderFile(string $filePath, array $data): string {
        if (!file_exists($filePath)) throw new \RuntimeException("View file not found: [{$filePath}]");
        extract($data, EXTR_SKIP);
        $view = $this;
        ob_start();
        include $filePath;
        return ob_get_clean();
    }
    private function resolvePath(string $view): string {
        $relative = str_replace('.','/',$view).'.php';
        return Application::getInstance()->path('resources/views').'/'.$relative;
    }
    public function e(mixed $value): string { return htmlspecialchars((string)($value??''),ENT_QUOTES|ENT_HTML5,'UTF-8'); }
    public function csrf(): string { return '<input type="hidden" name="_csrf_token" value="'.htmlspecialchars(Csrf::getToken(),ENT_QUOTES,'UTF-8').'">'; }
    public function csrfToken(): string { return Csrf::getToken(); }
    public function old(string $key, mixed $default=''): string { $old=Session::getInstance()->getFlash('old')??[]; return $this->e($old[$key]??$default); }
    public function asset(string $path): string { return url('assets/'.ltrim($path,'/')); }
    public function url(string $path=''): string { return url($path); }
    public function route(string $name, array $params=[]): string { return Application::getInstance()->make(Router::class)->route($name,$params); }
}
