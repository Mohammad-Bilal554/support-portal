<?php
declare(strict_types=1);
namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;

class LogoutController extends Controller
{
    public function logout(Request $request): never
    {
        (new AuthService())->logout($request->ip());
        $this->session->success('You have been logged out successfully.');
        $this->redirect(url('auth/login'));
    }
}
