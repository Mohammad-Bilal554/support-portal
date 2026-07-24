<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Core\Controller;
use App\Core\Request;
class UserController extends Controller
{
    public function __call(string $name, array $args): string
    {
        return '<h2 style="font-family:monospace;padding:2rem;color:#3b82f6;">'
             . '🚧 UserController::${name}() — To be built in upcoming modules.</h2>';
    }
}
