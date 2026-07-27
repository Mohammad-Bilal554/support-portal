<?php
declare(strict_types=1);
namespace App\Core;

abstract class Controller {
    protected Response $response;
    protected Session  $session;

    public function __construct() { $this->response=new Response(); $this->session=Session::getInstance(); }

    protected function view(string $view, array $data=[]): string {
        $user=$this->session->getUser();
        if ($user) $data['authUser']=$user;
        return View::make($view,$data)->render();
    }
    protected function json(mixed $data, int $status=200): string {
        http_response_code($status); header('Content-Type: application/json');
        return json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
    protected function jsonSuccess(mixed $data=null, string $message='Success', int $status=200): string {
        return $this->json(['success'=>true,'message'=>$message,'data'=>$data],$status);
    }
    protected function jsonError(string $message='Error', int $status=400, array $errors=[]): string {
        $p=['success'=>false,'message'=>$message]; if(!empty($errors))$p['errors']=$errors;
        return $this->json($p,$status);
    }
    protected function redirect(string $url): never { header('Location: '.$url); exit; }
    protected function redirectBack(): never { $this->redirect($_SERVER['HTTP_REFERER']??url('/')); }
    protected function redirectRoute(string $name, array $params=[]): never {
        $this->redirect(Application::getInstance()->make(Router::class)->route($name,$params));
    }
    protected function withSuccess(string $message, string $url=null): never {
        $this->session->success($message); $url ? $this->redirect($url) : $this->redirectBack();
    }
    protected function withError(string $message, string $url=null): never {
        $this->session->error($message); $url ? $this->redirect($url) : $this->redirectBack();
    }
    protected function auth(): ?array        { return $this->session->getUser(); }
    protected function authId(): ?int        { return $this->session->getUserId(); }
    protected function authRole(): ?string   { return $this->session->getUserRole(); }
    protected function isAdmin(): bool       { return $this->authRole()==='super_admin'; }
    protected function isEmployee(): bool    { return in_array($this->authRole(),['super_admin','employee']); }
    protected function isClient(): bool      { return $this->authRole()==='client'; }
    protected function requireLogin(): void {
        if (!$this->session->isLoggedIn()) {
            $this->session->setFlash('intended_url',current_url());
            $this->redirect(url('auth/login'));
        }
    }
    protected function authorize(bool $condition, int $status=403, string $message='Forbidden'): void {
        if (!$condition) { http_response_code($status); echo "<h1>{$status} {$message}</h1>"; exit; }
    }
    protected function isAjax(): bool { return ($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='XMLHttpRequest'; }
    protected function abort(int $code, string $message=''): never {
        http_response_code($code);
        $p=Application::getInstance()->path("resources/views/errors/{$code}.php");
        file_exists($p)?include $p:print("<h1>{$code}</h1><p>{$message}</p>"); exit;
    }
}
