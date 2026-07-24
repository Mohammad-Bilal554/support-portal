<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;

/**
 * AuthMiddleware
 *
 * Protects routes from unauthenticated access.
 * Redirects guests to login; stores intended URL.
 */
class AuthMiddleware
{
    public function handle(Request $request, callable $next): void
    {
        $session = Session::getInstance();

        if (! $session->isLoggedIn()) {
            // Store where they were trying to go
            $session->setFlash('intended_url', $request->fullUrl());

            if ($request->isAjax() || $request->isJson()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthenticated.']);
                return;
            }

            redirect(url('auth/login'));
        }

        // Check if user account is still active
        $user = $session->getUser();
        if (isset($user['is_active']) && ! $user['is_active']) {
            $session->destroy();
            redirect(url('auth/login') . '?reason=deactivated');
        }

        $next();
    }
}
