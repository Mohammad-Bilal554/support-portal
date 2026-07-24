<?php
declare(strict_types=1);
namespace App\Controllers\Auth;
use App\Core\Controller;
use App\Core\Request;
class LoginController extends Controller
{
    public function __call(string $name, array $args): string
    {
        return '<h2 style="font-family:monospace;padding:2rem;color:#3b82f6;">'
             . '🚧 LoginController::${name}() — Module 2 (Auth) not yet built.</h2>';
    }
}
