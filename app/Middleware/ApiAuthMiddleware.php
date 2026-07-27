<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Core\Database; use App\Core\Request;
class ApiAuthMiddleware {
    public function handle(Request $request, callable $next): void {
        $token=$request->bearerToken();
        if (!$token) { http_response_code(401); header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'No token provided.']); return; }
        $db=$row=Database::getInstance()->fetchOne('SELECT t.*,u.id as user_id,u.role,u.first_name,u.last_name,u.email,u.is_active FROM api_tokens t JOIN users u ON u.id=t.user_id WHERE t.token=? AND (t.expires_at IS NULL OR t.expires_at>NOW()) LIMIT 1',[$token]);
        if (!$row||!$row['is_active']) { http_response_code(401); header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Invalid or expired token.']); return; }
        Database::getInstance()->update('api_tokens',['last_used'=>date('Y-m-d H:i:s')],['token'=>$token]);
        $_SERVER['API_USER']=$row;
        $next();
    }
}
