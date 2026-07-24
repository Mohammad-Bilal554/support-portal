<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;

/**
 * RoleMiddleware
 *
 * Restricts routes to specified roles.
 * Usage in routes: ->middleware('role:super_admin,employee')
 */
class RoleMiddleware
{
    private array $allowedRoles;

    public function __construct(string ...$roles)
    {
        $this->allowedRoles = $roles;
    }

    public function handle(Request $request, callable $next): void
    {
        $user = Session::getInstance()->getUser();
        $role = $user['role'] ?? null;

        if (! $role || ! in_array($role, $this->allowedRoles, true)) {
            if ($request->isAjax() || $request->isJson()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Access denied.']);
                return;
            }

            http_response_code(403);
            $errorPage = base_path('resources/views/errors/403.php');
            if (file_exists($errorPage)) {
                include $errorPage;
            } else {
                echo '<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>';
            }
            return;
        }

        $next();
    }
}
