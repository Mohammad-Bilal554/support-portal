<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\User;
use App\Models\Company;
use App\Services\UserService;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        parent::__construct();
        $this->userService = new UserService();
    }

    // GET /admin/users
    public function index(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $filters = [
            'search'     => $request->query('search', ''),
            'role'       => $request->query('role', ''),
            'company_id' => $request->query('company_id', ''),
            'is_active'  => $request->query('is_active', ''),
        ];
        $page    = max(1, $request->integer('page', 1));
        $perPage = 20;

        $users     = User::listPaginated($page, $perPage, $filters);
        $companies = Company::all(['is_active' => 1], 'name ASC');
        $counts    = User::countByRole();

        return $this->view('admin.users.index', [
            'title'       => 'User Management',
            'users'       => $users,
            'companies'   => $companies,
            'counts'      => $counts,
            'filters'     => $filters,
            'breadcrumbs' => [['label' => 'Admin'], ['label' => 'Users']],
        ]);
    }

    // GET /admin/users/create
    public function create(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $companies = Company::all(['is_active' => 1], 'name ASC');

        return $this->view('admin.users.create', [
            'title'       => 'Create User',
            'companies'   => $companies,
            'breadcrumbs' => [['label' => 'Admin'], ['label' => 'Users', 'url' => url('admin/users')], ['label' => 'Create']],
        ]);
    }

    // POST /admin/users
    public function store(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $data      = $request->all();
        $validator = new Validator($data, [
            'first_name' => 'required|min_length:2|max_length:80',
            'last_name'  => 'required|min_length:2|max_length:80',
            'email'      => 'required|email|max_length:150',
            'password'   => 'required|min_length:8',
            'role'       => 'required|in:super_admin,employee,client',
            'phone'      => 'nullable|max_length:30',
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('errors', $validator->errors());
            $this->session->setFlash('old', $request->except(['password']));
            $this->redirect(url('admin/users/create'));
        }

        $avatarFile = $request->hasFile('avatar') ? $request->file('avatar') : null;
        $result     = $this->userService->create($data, $avatarFile);

        if (!$result['success']) {
            $this->session->setFlash('error', $result['message']);
            $this->session->setFlash('old', $request->except(['password']));
            $this->redirect(url('admin/users/create'));
        }

        $this->session->success('User created successfully.');
        $this->redirect(url('admin/users'));
    }

    // GET /admin/users/{id}
    public function edit(Request $request, string $id): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $user      = User::findOrFail((int)$id);
        $companies = Company::all(['is_active' => 1], 'name ASC');

        return $this->view('admin.users.edit', [
            'title'       => 'Edit User',
            'editUser'    => $user,
            'companies'   => $companies,
            'breadcrumbs' => [
                ['label' => 'Admin'],
                ['label' => 'Users', 'url' => url('admin/users')],
                ['label' => User::fullName($user)],
            ],
        ]);
    }

    // POST /admin/users/{id}
    public function update(Request $request, string $id): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $data      = $request->all();
        $userId    = (int)$id;
        $validator = new Validator($data, [
            'first_name' => 'required|min_length:2|max_length:80',
            'last_name'  => 'required|min_length:2|max_length:80',
            'email'      => 'required|email|max_length:150',
            'role'       => 'required|in:super_admin,employee,client',
            'phone'      => 'nullable|max_length:30',
            'password'   => 'nullable|min_length:8',
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('errors', $validator->errors());
            $this->session->setFlash('old', $request->except(['password']));
            $this->redirect(url("admin/users/{$userId}"));
        }

        $avatarFile = $request->hasFile('avatar') ? $request->file('avatar') : null;
        $result     = $this->userService->update($userId, $data, $avatarFile);

        if (!$result['success']) {
            $this->session->setFlash('error', $result['message']);
            $this->redirect(url("admin/users/{$userId}"));
        }

        // Refresh session if editing own profile
        if ($userId === $this->authId()) {
            $updatedUser = User::find($userId);
            unset($updatedUser['password']);
            $this->session->setUser($updatedUser);
        }

        $this->session->success('User updated successfully.');
        $this->redirect(url('admin/users'));
    }

    // DELETE /admin/users/{id}
    public function destroy(Request $request, string $id): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $result = $this->userService->delete((int)$id);

        if ($this->isAjax()) {
            return $this->json($result);
        }

        if ($result['success']) {
            $this->session->success($result['message']);
        } else {
            $this->session->error($result['message']);
        }

        $this->redirect(url('admin/users'));
    }

    // POST /admin/users/{id}/toggle  (AJAX)
    public function toggleStatus(Request $request, string $id): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $result = $this->userService->toggleActive((int)$id);
        return $this->json($result);
    }
}
