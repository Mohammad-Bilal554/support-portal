<?php
declare(strict_types=1);
namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Validator;
use App\Services\AuthService;

class ResetPasswordController extends Controller
{
    public function show(Request $request, string $token): string
    {
        if (! (new AuthService())->validateResetToken($token)) {
            $this->session->setFlash('error', 'This password reset link is invalid or has expired.');
            $this->redirect(url('auth/forgot-password'));
        }
        return $this->view('auth.reset-password', [
            'title'     => 'Reset Password',
            'token'     => $token,
            'csrfToken' => Csrf::getToken(),
        ]);
    }

    public function reset(Request $request): string
    {
        $validator = new Validator($request->all(), [
            'token'                 => 'required',
            'password'              => 'required|min_length:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('errors', $validator->errors());
            $this->redirect(url('auth/reset-password/' . $request->input('token')));
        }

        $result = (new AuthService())->resetPassword(
            (string) $request->input('token'),
            (string) $request->input('password')
        );

        if (! $result['success']) {
            $this->session->setFlash('error', $result['message']);
            $this->redirect(url('auth/forgot-password'));
        }

        $this->session->setFlash('success', 'Password reset successfully. Please sign in.');
        $this->redirect(url('auth/login'));
    }
}
