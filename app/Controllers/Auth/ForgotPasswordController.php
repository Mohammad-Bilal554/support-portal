<?php
declare(strict_types=1);
namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Validator;
use App\Models\User;
use App\Services\AuthService;
use App\Services\EmailService;

class ForgotPasswordController extends Controller
{
    public function show(Request $request): string
    {
        if ($this->session->isLoggedIn()) {
            $this->redirect(url('dashboard'));
        }
        return $this->view('auth.forgot-password', [
            'title'     => 'Forgot Password',
            'csrfToken' => Csrf::getToken(),
        ]);
    }

    public function send(Request $request): string
    {
        $validator = new Validator($request->all(), [
            'email' => 'required|email|max_length:150',
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('errors', $validator->errors());
            $this->session->setFlash('old', $request->only(['email']));
            $this->redirect(url('auth/forgot-password'));
        }

        $email = strtolower(trim((string) $request->input('email')));
        $token = (new AuthService())->createPasswordResetToken($email);

        if ($token) {
            $user     = User::findByEmail($email);
            $fullName = User::fullName($user);
            (new EmailService())->sendPasswordReset($email, $fullName, url('auth/reset-password/' . $token));
        }

        $this->session->setFlash('success', 'If that email exists in our system, you will receive a reset link shortly.');
        $this->redirect(url('auth/forgot-password'));
    }
}
