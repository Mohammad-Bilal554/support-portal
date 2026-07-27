<?php
declare(strict_types=1);
namespace App\Core;

class Request {
    private array $queryParams;
    private array $bodyParams;
    private array $files;
    private array $serverParams;
    private array $headers;
    private ?array $jsonBody = null;

    public function __construct() {
        $this->queryParams  = $_GET    ?? [];
        $this->bodyParams   = $_POST   ?? [];
        $this->files        = $_FILES  ?? [];
        $this->serverParams = $_SERVER ?? [];
        $this->headers      = $this->parseHeaders();
        $this->parseJsonBody();
    }
    public function method(): string { return strtoupper($this->serverParams['REQUEST_METHOD']??'GET'); }
    public function isGet(): bool    { return $this->method()==='GET'; }
    public function isPost(): bool   { return $this->method()==='POST'; }
    public function isPut(): bool    { return $this->method()==='PUT'; }
    public function isPatch(): bool  { return $this->method()==='PATCH'; }
    public function isDelete(): bool { return $this->method()==='DELETE'; }
    public function isAjax(): bool   { return ($this->header('X-Requested-With')??'')==='XMLHttpRequest'; }
    public function isJson(): bool   { return str_contains($this->header('Content-Type')??'','application/json'); }
    public function uri(): string    { return $this->serverParams['REQUEST_URI']??'/'; }
    public function pathInfo(): string {
        $uri = parse_url($this->uri(),PHP_URL_PATH) ?? '/';
        $dir = dirname($this->serverParams['SCRIPT_NAME']??'');
        if ($dir!=='/'&&$dir!=='\\') $uri = preg_replace('#^'.preg_quote($dir,'#').'#','',$uri);
        return '/'.ltrim($uri,'/');
    }
    public function fullUrl(): string {
        $s = isset($this->serverParams['HTTPS'])&&$this->serverParams['HTTPS']!=='off'?'https':'http';
        return $s.'://'.($this->serverParams['HTTP_HOST']??'localhost').$this->uri();
    }
    public function input(string $key, mixed $default=null): mixed {
        if ($this->jsonBody!==null&&array_key_exists($key,$this->jsonBody)) return $this->jsonBody[$key];
        return $this->bodyParams[$key]??$this->queryParams[$key]??$default;
    }
    public function all(): array {
        $base = array_merge($this->queryParams,$this->bodyParams);
        return $this->jsonBody!==null ? array_merge($base,$this->jsonBody) : $base;
    }
    public function only(array $keys): array { return array_intersect_key($this->all(),array_flip($keys)); }
    public function except(array $keys): array { return array_diff_key($this->all(),array_flip($keys)); }
    public function has(string $key): bool { return $this->input($key)!==null; }
    public function filled(string $key): bool { $v=$this->input($key); return $v!==null&&$v!==''; }
    public function query(string $key, mixed $default=null): mixed { return $this->queryParams[$key]??$default; }
    public function safe(string $key, mixed $default=null): ?string {
        $v=$this->input($key,$default); return $v!==null?htmlspecialchars((string)$v,ENT_QUOTES|ENT_HTML5,'UTF-8'):null;
    }
    public function integer(string $key, int $default=0): int { return (int)$this->input($key,$default); }
    public function boolean(string $key, bool $default=false): bool { return filter_var($this->input($key,$default),FILTER_VALIDATE_BOOLEAN); }
    public function header(string $name): ?string { return $this->headers[strtolower($name)]??null; }
    public function bearerToken(): ?string {
        $auth=$this->header('Authorization')??'';
        return str_starts_with($auth,'Bearer ') ? substr($auth,7) : null;
    }
    private function parseHeaders(): array {
        $headers=[];
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $n=>$v) $headers[strtolower($n)]=$v;
            return $headers;
        }
        foreach ($this->serverParams as $k=>$v) {
            if (str_starts_with($k,'HTTP_')) $headers[strtolower(str_replace('_','-',substr($k,5)))]=$v;
            elseif (in_array($k,['CONTENT_TYPE','CONTENT_LENGTH'])) $headers[strtolower(str_replace('_','-',$k))]=$v;
        }
        return $headers;
    }
    private function parseJsonBody(): void {
        if ($this->isJson()&&in_array($this->method(),['POST','PUT','PATCH'])) {
            $body=file_get_contents('php://input');
            if ($body) { $d=json_decode($body,true); if (json_last_error()===JSON_ERROR_NONE) $this->jsonBody=$d; }
        }
    }
    public function file(string $key): ?array { return $this->files[$key]??null; }
    public function hasFile(string $key): bool { return isset($this->files[$key])&&$this->files[$key]['error']!==UPLOAD_ERR_NO_FILE; }
    public function ip(): string {
        foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
            if (!empty($this->serverParams[$k])) {
                $ip=trim(explode(',',$this->serverParams[$k])[0]);
                if (filter_var($ip,FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }
    public function userAgent(): string { return $this->serverParams['HTTP_USER_AGENT']??''; }
    public function validate(array $rules): array {
        $v = new Validator($this->all(),$rules);
        if ($v->fails()) {
            if ($this->isAjax()||$this->isJson()) {
                http_response_code(422); header('Content-Type: application/json');
                echo json_encode(['success'=>false,'errors'=>$v->errors()]); exit;
            }
            Session::getInstance()->setFlash('errors',$v->errors());
            Session::getInstance()->setFlash('old',$this->all());
            redirect_back();
        }
        return $v->validated();
    }
}
