<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\Request;
use App\Models\Company;
use App\Services\CompanyService;

/**
 * Company API Controller
 *
 * GET    /api/v1/companies           List companies (admin only)
 * POST   /api/v1/companies           Create company (admin only)
 * GET    /api/v1/companies/{id}      Get single company with stats
 * PUT    /api/v1/companies/{id}      Update company (admin only)
 * DELETE /api/v1/companies/{id}      Delete company (admin only)
 */
class CompanyApiController extends ApiController
{
    private CompanyService $companyService;

    public function __construct(Request $request)
    {
        parent::__construct();
        $this->request        = $request;
        $this->companyService = new CompanyService();
    }

    // GET /api/v1/companies
    public function index(Request $request): string
    {
        if (!$this->isAdmin()) return $this->forbidden();

        $filters = [
            'search'    => $request->query('search',    ''),
            'is_active' => $request->query('is_active', ''),
        ];

        $companies = Company::listPaginated($this->page(), $this->perPage(50), $filters);
        $companies['data'] = array_map([$this, 'formatCompany'], $companies['data']);

        return $this->paginated($companies, 'Companies retrieved.');
    }

    // GET /api/v1/companies/{id}
    public function show(Request $request, string $id): string
    {
        if (!$this->isAdmin()) return $this->forbidden();

        $company = Company::findWithStats((int)$id);
        if (!$company) return $this->notFound('Company not found.');

        $users   = Company::getUsers((int)$id);
        $tickets = Company::getTickets((int)$id, 5);

        return $this->success([
            'company'      => $this->formatCompany($company),
            'users'        => $users,
            'recent_tickets' => $tickets,
        ]);
    }

    // POST /api/v1/companies
    public function store(Request $request): string
    {
        if (!$this->isAdmin()) return $this->forbidden();

        $data = $this->validate($request->all(), [
            'name'    => 'required|min_length:2|max_length:150',
            'email'   => 'required|email|max_length:150',
            'phone'   => 'nullable|max_length:30',
            'website' => 'nullable|max_length:255',
            'address' => 'nullable|max_length:500',
        ]);

        $result = $this->companyService->create($data);

        if (!$result['success']) return $this->error($result['message'], 422);

        $company = Company::findWithStats($result['id']);
        return $this->created($this->formatCompany($company), $result['message']);
    }

    // PUT /api/v1/companies/{id}
    public function update(Request $request, string $id): string
    {
        if (!$this->isAdmin()) return $this->forbidden();

        $company = Company::find((int)$id);
        if (!$company) return $this->notFound('Company not found.');

        $data = $this->validate($request->all(), [
            'name'    => 'required|min_length:2|max_length:150',
            'email'   => 'required|email|max_length:150',
            'phone'   => 'nullable|max_length:30',
            'website' => 'nullable|max_length:255',
            'address' => 'nullable|max_length:500',
        ]);

        $result = $this->companyService->update((int)$id, $data);

        if (!$result['success']) return $this->error($result['message'], 422);

        return $this->success(
            $this->formatCompany(Company::findWithStats((int)$id)),
            $result['message']
        );
    }

    // DELETE /api/v1/companies/{id}
    public function destroy(Request $request, string $id): string
    {
        if (!$this->isAdmin()) return $this->forbidden();

        $result = $this->companyService->delete((int)$id);

        if (!$result['success']) return $this->error($result['message'], 422);

        return $this->success(null, $result['message']);
    }

    // ── Formatter ─────────────────────────────────────────────────

    private function formatCompany(?array $c): ?array
    {
        if (!$c) return null;
        return [
            'id'           => (int)$c['id'],
            'name'         => $c['name'],
            'email'        => $c['email'],
            'phone'        => $c['phone']   ?? null,
            'website'      => $c['website'] ?? null,
            'address'      => $c['address'] ?? null,
            'logo_url'     => Company::logoUrl($c),
            'is_active'    => (bool)$c['is_active'],
            'user_count'   => (int)($c['user_count']   ?? 0),
            'ticket_count' => (int)($c['ticket_count'] ?? 0),
            'open_tickets' => (int)($c['open_tickets'] ?? 0),
            'created_at'   => $c['created_at'],
        ];
    }
}
