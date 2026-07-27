<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Core\Csrf; use App\Core\Request;
class CsrfMiddleware {
    public function handle(Request $request, callable $next): void {
        if (!Csrf::verify($request)) {
            if ($request->isAjax()||$request->isJson()) { http_response_code(419); header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'CSRF token mismatch.']); return; }
            http_response_code(419); echo '<h1>419 – Session Expired</h1><p><a href="javascript:history.back()">Go back</a> and try again.</p>'; return;
        }
        $next();
    }
}
