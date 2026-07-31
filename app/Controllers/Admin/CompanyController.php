<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Company;
use App\Models\User;
use App\Services\CompanyService;

class CompanyController extends Controller
{
    private CompanyService $companyService;

    public function __construct()
    {
        parent::__construct();
        $this->companyService = new CompanyService();
    }

    // GET /admin/companies
    public function index(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $filters = [
            'search'    => $request->query('search', ''),
            'is_active' => $request->query('is_active', ''),
        ];
        $page = max(1, $request->integer('page', 1));

        $companies = Company::listPaginated($page, 15, $filters);
        $summary   = Company::getSummary();

        return $this->view('admin.companies.index', [
            'title'       => 'Company Management',
            'companies'   => $companies,
            'summary'     => $summary,
            'filters'     => $filters,
            'breadcrumbs' => [['label' => 'Admin'], ['label' => 'Companies']],
        ]);
    }

    // GET /admin/companies/create
    public function create(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        return $this->view('admin.companies.create', [
            'title'       => 'Add Company',
            'breadcrumbs' => [
                ['label' => 'Admin'],
                ['label' => 'Companies', 'url' => url('admin/companies')],
                ['label' => 'Add Company'],
            ],
        ]);
    }

    // POST /admin/companies
    public function store(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $validator = new Validator($request->all(), [
            'name'    => 'required|min_length:2|max_length:150',
            'email'   => 'required|email|max_length:150',
            'phone'   => 'nullable|max_length:30',
            'website' => 'nullable|max_length:255',
            'address' => 'nullable|max_length:500',
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('errors', $validator->errors());
            $this->session->setFlash('old', $request->all());
            $this->redirect(url('admin/companies/create'));
        }

        $logoFile = $request->hasFile('logo') ? $request->file('logo') : null;
        $result   = $this->companyService->create($request->all(), $logoFile);

        if (!$result['success']) {
            $this->session->setFlash('error', $result['message']);
            $this->session->setFlash('old', $request->all());
            $this->redirect(url('admin/companies/create'));
        }

        $this->session->success('Company created successfully.');
        $this->redirect(url('admin/companies'));
    }

    // GET /admin/companies/{id}
    public function edit(Request $request, string $id): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $company = Company::findWithStats((int)$id);
        if (!$company) {
            $this->abort(404, 'Company not found.');
        }

        $users   = Company::getUsers((int)$id);
        $tickets = Company::getTickets((int)$id, 8);

        return $this->view('admin.companies.edit', [
            'title'       => 'Edit Company',
            'company'     => $company,
            'users'       => $users,
            'tickets'     => $tickets,
            'breadcrumbs' => [
                ['label' => 'Admin'],
                ['label' => 'Companies', 'url' => url('admin/companies')],
                ['label' => htmlspecialchars($company['name'])],
            ],
        ]);
    }

    // POST /admin/companies/{id}
    public function update(Request $request, string $id): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $validator = new Validator($request->all(), [
            'name'    => 'required|min_length:2|max_length:150',
            'email'   => 'required|email|max_length:150',
            'phone'   => 'nullable|max_length:30',
            'website' => 'nullable|max_length:255',
            'address' => 'nullable|max_length:500',
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('errors', $validator->errors());
            $this->session->setFlash('old', $request->all());
            $this->redirect(url("admin/companies/{$id}"));
        }

        $logoFile = $request->hasFile('logo') ? $request->file('logo') : null;
        $result   = $this->companyService->update((int)$id, $request->all(), $logoFile);

        if (!$result['success']) {
            $this->session->setFlash('error', $result['message']);
            $this->redirect(url("admin/companies/{$id}"));
        }

        $this->session->success('Company updated successfully.');
        $this->redirect(url('admin/companies'));
    }

    // DELETE /admin/companies/{id}
    public function destroy(Request $request, string $id): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $result = $this->companyService->delete((int)$id);

        if ($this->isAjax()) {
            return $this->json($result);
        }

        if ($result['success']) {
            $this->session->success($result['message']);
        } else {
            $this->session->error($result['message']);
        }

        $this->redirect(url('admin/companies'));
    }

    // POST /admin/companies/{id}/toggle
    public function toggle(Request $request, string $id): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $result = $this->companyService->toggleActive((int)$id);
        return $this->json($result);
    }
}
