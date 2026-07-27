<?php
declare(strict_types=1);
namespace App\Core;

class Response {
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';

    public function setStatusCode(int $code): static { $this->statusCode=$code; return $this; }
    public function setHeader(string $name, string $value): static { $this->headers[$name]=$value; return $this; }
    public function withHeaders(array $headers): static { foreach($headers as $n=>$v) $this->setHeader($n,$v); return $this; }
    public function setBody(string $body): static { $this->body=$body; return $this; }
    public function html(string $content, int $status=200): static { $this->statusCode=$status; $this->setHeader('Content-Type','text/html; charset=UTF-8'); $this->body=$content; return $this; }
    public function json(mixed $data, int $status=200): static { $this->statusCode=$status; $this->setHeader('Content-Type','application/json'); $this->body=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); return $this; }
    public function jsonSuccess(mixed $data=null, string $message='Success', int $status=200): void { $this->json(['success'=>true,'message'=>$message,'data'=>$data],$status)->send(); }
    public function jsonError(string $message='Error', int $status=400, array $errors=[]): void { $p=['success'=>false,'message'=>$message]; if(!empty($errors))$p['errors']=$errors; $this->json($p,$status)->send(); }
    public function redirect(string $url, int $status=302): static { $this->statusCode=$status; $this->setHeader('Location',$url); $this->body=''; return $this; }
    public function send(): void {
        if (!headers_sent()) { http_response_code($this->statusCode); foreach($this->headers as $n=>$v) header("$n: $v"); }
        echo $this->body;
    }
    public function sendAndExit(): never { $this->send(); exit; }
}
