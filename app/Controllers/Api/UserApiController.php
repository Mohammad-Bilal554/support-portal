<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\Request;
use App\Models\User;
use App\Services\UserService;

/**
 * User API Controller
 *
 * GET    /api/v1/users           List users (admin only)
 * POST   /api/v1/users           Create user (admin only)
 * GET    /api/v1/users/me        Get authenticated user profile
 * GET    /api/v1/users/{id}      Get single user (admin only)
 * PUT    /api/v1/users/{id}      Update user (admin only)
 * DELETE /api/v1/users/{id}      Delete/deactivate user (admin only)
 */
class UserApiController extends ApiController
{
    private UserService $userService;

    public function __construct(Request $request)
    {
        parent::__construct();
        $this->request     = $request;
        $this->userService = new UserService();
    }

    // GET /api/v1/users/me
    public function me(Request $request): string
    {
        $user = User::find($this->userId());
        if (!$user) return $this->notFound('User not found.');
        return $this->success($this->formatUser($user));
    }

    // GET /api/v1/users
    public function index(Request $request): string
    {
        if (!$this->isAdmin()) return $this->forbidden();

        $filters = [
            'search'    => $request->query('search',    ''),
            'role'      => $request->query('role',      ''),
            'is_active' => $request->query('is_active', ''),
            'company_id'=> $request->query('company_id',''),
        ];

        $users = User::listPaginated($this->page(), $this->perPage(50), $filters);
        $users['data'] = array_map([$this, 'formatUser'], $users['data']);

        return $this->paginated($users, 'Users retrieved.');
    }

    // GET /api/v1/users/{id}
    public function show(Request $request, string $id): string
    {
        if (!$this->isAdmin()) return $this->forbidden();

        $user = User::find((int)$id);
        if (!$user) return $this->notFound('User not found.');

        return $this->success($this->formatUser($user));
    }

    // POST /api/v1/users
    public function store(Request $request): string
    {
        if (!$this->isAdmin()) return $this->forbidden();

        $data = $this->validate($request->all(), [
            'first_name' => 'required|min_length:2|max_length:80',
            'last_name'  => 'required|min_length:2|max_length:80',
            'email'      => 'required|email|max_length:150',
            'password'   => 'required|min_length:8',
            'role'       => 'required|in:super_admin,employee,client',
        ]);

        $result = $this->userService->create($data);

        if (!$result['success']) return $this->error($result['message'], 422);

        $user = User::find($result['id']);
        return $this->created($this->formatUser($user), $result['message']);
    }

    // PUT /api/v1/users/{id}
    public function update(Request $request, string $id): string
    {
        if (!$this->isAdmin()) return $this->forbidden();

        $user = User::find((int)$id);
        if (!$user) return $this->notFound('User not found.');

        $data = $this->validate($request->all(), [
            'first_name' => 'required|min_length:2|max_length:80',
            'last_name'  => 'required|min_length:2|max_length:80',
            'email'      => 'required|email|max_length:150',
            'role'       => 'required|in:super_admin,employee,client',
        ]);

        $result = $this->userService->update((int)$id, $data);

        if (!$result['success']) return $this->error($result['message'], 422);

        return $this->success(
            $this->formatUser(User::find((int)$id)),
            $result['message']
        );
    }

    // DELETE /api/v1/users/{id}
    public function destroy(Request $request, string $id): string
    {
        if (!$this->isAdmin()) return $this->forbidden();

        if ((int)$id === $this->userId()) {
            return $this->error('You cannot delete your own account.');
        }

        $result = $this->userService->delete((int)$id);

        if (!$result['success']) return $this->error($result['message'], 422);

        return $this->success(null, $result['message']);
    }

    // ── Formatter ─────────────────────────────────────────────────

    private function formatUser(?array $u): ?array
    {
        if (!$u) return null;
        return [
            'id'          => (int)$u['id'],
            'first_name'  => $u['first_name'],
            'last_name'   => $u['last_name'],
            'full_name'   => trim($u['first_name'] . ' ' . $u['last_name']),
            'email'       => $u['email'],
            'phone'       => $u['phone'] ?? null,
            'role'        => $u['role'],
            'company_id'  => $u['company_id'] ? (int)$u['company_id'] : null,
            'is_active'   => (bool)$u['is_active'],
            'avatar_url'  => User::avatarUrl($u),
            'last_login'  => $u['last_login'] ?? null,
            'created_at'  => $u['created_at'],
        ];
    }
}
