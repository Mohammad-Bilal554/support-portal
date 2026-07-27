<?php
declare(strict_types=1);
namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Validator;
use App\Services\AuthService;

class LoginController extends Controller
{
    public function showLogin(Request $request): string
    {
        if ($this->session->isLoggedIn()) {
            $this->redirect(url('dashboard'));
        }
        return $this->view('auth.login', [
            'title'     => 'Sign In',
            'csrfToken' => Csrf::getToken(),
        ]);
    }

    public function login(Request $request): string
    {
        $validator = new Validator($request->all(), [
            'email'    => 'required|email|max_length:150',
            'password' => 'required|min_length:1',
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('errors', $validator->errors());
            $this->session->setFlash('old', $request->only(['email']));
            $this->redirect(url('auth/login'));
        }

        $result = (new AuthService())->attempt(
            (string) $request->input('email'),
            (string) $request->input('password'),
            $request->ip()
        );

        if (! $result['success']) {
            $this->session->setFlash('error', $result['message']);
            $this->session->setFlash('old', $request->only(['email']));
            $this->redirect(url('auth/login'));
        }

        $intended = $this->session->getFlash('intended_url');
        $this->redirect($intended ?: url('dashboard'));
    }
}
