<?php
declare(strict_types=1);
namespace App\Controllers\Ticket;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\Company;
use App\Models\User;
use App\Services\TicketService;

class TicketController extends Controller
{
    private TicketService $ticketService;

    public function __construct()
    {
        parent::__construct();
        $this->ticketService = new TicketService();
    }

    // GET /tickets
    public function index(Request $request): string
    {
        $this->requireLogin();
        $user = $this->auth();
        $role = $user['role'];

        $filters = [
            'search'      => $request->query('search', ''),
            'status'      => $request->query('status', ''),
            'priority'    => $request->query('priority', ''),
            'category_id' => $request->query('category_id', ''),
            'company_id'  => $request->query('company_id', ''),
            'assigned_to' => $request->query('assigned_to', ''),
            'my_tickets'  => $request->query('my_tickets', ''),
        ];

        $page    = max(1, $request->integer('page', 1));
        $perPage = 20;

        $tickets    = Ticket::listPaginated($page, $perPage, $filters, $role, (int)$user['id']);
        $categories = TicketCategory::getAllActive();
        $employees  = User::getAllEmployees();
        $companies  = $this->isAdmin() ? Company::getAllActive() : [];

        return $this->view('tickets.index', [
            'title'       => 'Tickets',
            'tickets'     => $tickets,
            'categories'  => $categories,
            'employees'   => $employees,
            'companies'   => $companies,
            'filters'     => $filters,
            'breadcrumbs' => [['label' => 'Tickets']],
        ]);
    }

    // GET /tickets/create
    public function create(Request $request): string
    {
        $this->requireLogin();
        $user = $this->auth();

        $categories = TicketCategory::getAllActive();
        $employees  = User::getAllEmployees();
        $companies  = Company::getAllActive();

        return $this->view('tickets.create', [
            'title'       => 'New Ticket',
            'categories'  => $categories,
            'employees'   => $employees,
            'companies'   => $companies,
            'breadcrumbs' => [
                ['label' => 'Tickets', 'url' => url('tickets')],
                ['label' => 'New Ticket'],
            ],
        ]);
    }

    // POST /tickets
    public function store(Request $request): string
    {
        $this->requireLogin();
        $user = $this->auth();

        $validator = new Validator($request->all(), [
            'subject'     => 'required|min_length:5|max_length:255',
            'description' => 'required|min_length:10',
            'priority'    => 'required|in:low,medium,high,critical',
            'category_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('errors', $validator->errors());
            $this->session->setFlash('old', $request->except(['description']));
            $this->redirect(url('tickets/create'));
        }

        // If client, override company_id from their profile
        $data = $request->all();
        if ($user['role'] === 'client') {
            $data['company_id'] = $user['company_id'] ?? null;
            unset($data['assigned_to']); // clients cannot self-assign
        }

        // Only admins can assign
        if ($user['role'] !== 'super_admin') {
            unset($data['assigned_to']);
        }

        $files  = $request->hasFile('attachments') ? $request->file('attachments') : [];
        $result = $this->ticketService->create($data, (int)$user['id'], $files);

        if (!$result['success']) {
            $this->session->setFlash('error', $result['message']);
            $this->redirect(url('tickets/create'));
        }

        $this->session->success("Ticket {$result['ticket_number']} created successfully.");
        $this->redirect(url('tickets/' . $result['ticket_id']));
    }

    // GET /tickets/{id}
    public function show(Request $request, string $id): string
    {
        $this->requireLogin();
        $user   = $this->auth();
        $ticket = Ticket::findWithDetails((int)$id);

        if (!$ticket) {
            $this->abort(404, 'Ticket not found.');
        }

        // Access control
        if ($user['role'] === 'client' && (int)$ticket['created_by'] !== (int)$user['id']) {
            $this->abort(403, 'Access denied.');
        }

        $includeInternal  = in_array($user['role'], ['super_admin', 'employee']);
        $conversations    = Ticket::getConversations((int)$id, $includeInternal);
        $attachments      = Ticket::getAttachments((int)$id);
        $statusHistory    = Ticket::getStatusHistory((int)$id);
        $employees        = User::getAllEmployees();
        $categories       = TicketCategory::getAllActive();

        // Available status transitions
        $transitions = Ticket::TRANSITIONS[$user['role']][$ticket['status']] ?? [];

        return $this->view('tickets.show', [
            'title'         => $ticket['ticket_number'] . ' — ' . $ticket['subject'],
            'ticket'        => $ticket,
            'conversations' => $conversations,
            'attachments'   => $attachments,
            'statusHistory' => $statusHistory,
            'employees'     => $employees,
            'categories'    => $categories,
            'transitions'   => $transitions,
            'breadcrumbs'   => [
                ['label' => 'Tickets', 'url' => url('tickets')],
                ['label' => $ticket['ticket_number']],
            ],
        ]);
    }

    // GET /tickets/{id}/edit
    public function edit(Request $request, string $id): string
    {
        $this->requireLogin();
        $this->authorize($this->isEmployee());

        $ticket     = Ticket::findWithDetails((int)$id);
        if (!$ticket) $this->abort(404);

        $categories = TicketCategory::getAllActive();
        $employees  = User::getAllEmployees();

        return $this->view('tickets.edit', [
            'title'       => 'Edit Ticket',
            'ticket'      => $ticket,
            'categories'  => $categories,
            'employees'   => $employees,
            'breadcrumbs' => [
                ['label' => 'Tickets', 'url' => url('tickets')],
                ['label' => $ticket['ticket_number'], 'url' => url('tickets/' . $id)],
                ['label' => 'Edit'],
            ],
        ]);
    }

    // POST /tickets/{id}
    public function update(Request $request, string $id): string
    {
        $this->requireLogin();
        $this->authorize($this->isEmployee());

        $validator = new Validator($request->all(), [
            'subject'     => 'required|min_length:5|max_length:255',
            'description' => 'required|min_length:10',
            'priority'    => 'required|in:low,medium,high,critical',
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('errors', $validator->errors());
            $this->redirect(url("tickets/{$id}/edit"));
        }

        $result = $this->ticketService->update((int)$id, $request->all(), (int)$this->authId());

        if (!$result['success']) {
            $this->session->setFlash('error', $result['message']);
        } else {
            $this->session->success($result['message']);
        }

        $this->redirect(url("tickets/{$id}"));
    }

    // DELETE /tickets/{id}
    public function destroy(Request $request, string $id): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $result = $this->ticketService->delete((int)$id, (int)$this->authId());

        if ($this->isAjax()) {
            return $this->json($result);
        }

        if ($result['success']) {
            $this->session->success($result['message']);
        } else {
            $this->session->error($result['message']);
        }

        $this->redirect(url('tickets'));
    }

    // POST /tickets/{id}/status  (AJAX)
    public function changeStatus(Request $request, string $id): string
    {
        $this->requireLogin();
        $user   = $this->auth();
        $status = $request->input('status', '');
        $note   = $request->input('note', '');

        $result = $this->ticketService->changeStatus(
            (int)$id, $status, $user['role'], (int)$user['id'], $note
        );

        return $this->json($result);
    }

    // POST /tickets/{id}/assign  (AJAX)
    public function assign(Request $request, string $id): string
    {
        $this->requireLogin();
        $this->authorize($this->isEmployee());

        $assigneeId = $request->integer('assigned_to');
        $result     = $this->ticketService->assign((int)$id, $assigneeId, (int)$this->authId());

        return $this->json($result);
    }
}
