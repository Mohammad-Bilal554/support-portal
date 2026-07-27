<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Core\Request; use App\Core\Session;
class RoleMiddleware {
    private array $allowedRoles;
    public function __construct(string ...$roles) { $this->allowedRoles=$roles; }
    public function handle(Request $request, callable $next): void {
        $role=Session::getInstance()->getUser()['role']??null;
        if (!$role||!in_array($role,$this->allowedRoles,true)) {
            if ($request->isAjax()||$request->isJson()) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Access denied.']); return; }
            http_response_code(403);
            $p=base_path('resources/views/errors/403.php');
            file_exists($p)?include $p:print('<h1>403 Forbidden</h1>'); return;
        }
        $next();
    }
}
