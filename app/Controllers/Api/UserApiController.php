<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\Controller;
use App\Core\Request;
class UserApiController extends Controller {{
    public function __call(string $name, array $args): string {{
        return '<div style="font-family:monospace;padding:2rem;background:#f8fafc;border-left:4px solid #0d6efd;border-radius:0 8px 8px 0;"><h3 style="color:#0d6efd;">🚧 UserApiController::$name()</h3><p>Coming in an upcoming module.</p></div>';
    }}
}}
