<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\User;
use App\Services\UserService;

class ProfileController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        parent::__construct();
        $this->userService = new UserService();
    }

    // GET /profile
    public function show(Request $request): string
    {
        $this->requireLogin();
        $user = User::find($this->authId());

        if (!$user) {
            $this->abort(404, 'User not found.');
        }

        return $this->view('profile.index', [
            'title'       => 'My Profile',
            'user'        => $user,
            'breadcrumbs' => [['label' => 'My Profile']],
        ]);
    }

    // POST /profile
    public function update(Request $request): string
    {
        $this->requireLogin();
        $userId = (int)$this->authId();

        $validator = new Validator($request->all(), [
            'first_name' => 'required|min_length:2|max_length:80',
            'last_name'  => 'required|min_length:2|max_length:80',
            'email'      => 'required|email|max_length:150',
            'phone'      => 'nullable|max_length:30',
            'password'   => 'nullable|min_length:8',
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('errors', $validator->errors());
            $this->session->setFlash('old', $request->except(['password']));
            $this->redirect(url('profile'));
        }

        $avatarFile = $request->hasFile('avatar') ? $request->file('avatar') : null;
        $result     = $this->userService->update($userId, $request->all(), $avatarFile);

        if (!$result['success']) {
            $this->session->setFlash('error', $result['message']);
            $this->redirect(url('profile'));
        }

        // Refresh user in session
        $updatedUser = User::find($userId);
        if ($updatedUser) {
            unset($updatedUser['password']);
            $this->session->setUser($updatedUser);
        }

        $this->session->success('Profile updated successfully.');
        $this->redirect(url('profile'));
    }
}
