<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\Controller;
use App\Core\Request;
class AuthApiController extends Controller
{
    public function __call(string $name, array $args): string
    {
        return $this->json(['success' => false, 'message' => 'AuthApiController::${name}() — Module 13 (API) not yet built.'], 501);
    }
}
