<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\Request;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\TicketService;

/**
 * Ticket API Controller
 *
 * GET    /api/v1/tickets                  List tickets (paginated, filtered)
 * POST   /api/v1/tickets                  Create ticket
 * GET    /api/v1/tickets/{id}             Get single ticket with conversations
 * PUT    /api/v1/tickets/{id}             Update ticket
 * DELETE /api/v1/tickets/{id}             Delete ticket (admin only)
 * POST   /api/v1/tickets/{id}/status      Change status
 * POST   /api/v1/tickets/{id}/assign      Assign ticket
 * POST   /api/v1/tickets/{id}/reply       Add reply
 * GET    /api/v1/tickets/{id}/history     Status history
 */
class TicketApiController extends ApiController
{
    private TicketService $ticketService;

    public function __construct(Request $request)
    {
        parent::__construct();
        $this->request       = $request;
        $this->ticketService = new TicketService();
    }

    // GET /api/v1/tickets
    public function index(Request $request): string
    {
        $filters = [
            'search'      => $request->query('search', ''),
            'status'      => $request->query('status', ''),
            'priority'    => $request->query('priority', ''),
            'category_id' => $request->query('category_id', ''),
            'company_id'  => $request->query('company_id', ''),
            'assigned_to' => $request->query('assigned_to', ''),
        ];

        $tickets = Ticket::listPaginated(
            $this->page(),
            $this->perPage(50),
            $filters,
            $this->userRole(),
            $this->userId()
        );

        // Clean up data for API response
        $tickets['data'] = array_map([$this, 'formatTicket'], $tickets['data']);

        return $this->paginated($tickets, 'Tickets retrieved.');
    }

    // GET /api/v1/tickets/{id}
    public function show(Request $request, string $id): string
    {
        $ticket = Ticket::findWithDetails((int)$id);

        if (!$ticket) {
            return $this->notFound('Ticket not found.');
        }

        // Role-based access
        if ($this->isClient() && (int)$ticket['created_by'] !== $this->userId()) {
            return $this->forbidden();
        }

        $includeInternal = $this->isEmployee();
        $conversations   = Ticket::getConversations((int)$id, $includeInternal);
        $attachments     = Ticket::getAttachments((int)$id);
        $history         = Ticket::getStatusHistory((int)$id);

        return $this->success([
            'ticket'        => $this->formatTicket($ticket),
            'conversations' => array_map([$this, 'formatConversation'], $conversations),
            'attachments'   => $attachments,
            'history'       => $history,
            'transitions'   => Ticket::TRANSITIONS[$this->userRole()][$ticket['status']] ?? [],
        ]);
    }

    // POST /api/v1/tickets
    public function store(Request $request): string
    {
        $data = $this->validate($request->all(), [
            'subject'     => 'required|min_length:5|max_length:255',
            'description' => 'required|min_length:10',
            'priority'    => 'required|in:low,medium,high,critical',
            'category_id' => 'nullable|integer',
        ]);

        // Clients always use their own company
        if ($this->isClient()) {
            $data['company_id'] = $this->user()['company_id'] ?? null;
            unset($data['assigned_to']);
        }
        if (!$this->isAdmin()) {
            unset($data['assigned_to']);
        }

        $result = $this->ticketService->create($data, $this->userId());

        if (!$result['success']) {
            return $this->error($result['message']);
        }

        $ticket = Ticket::findWithDetails($result['ticket_id']);
        return $this->created($this->formatTicket($ticket), $result['message']);
    }

    // PUT /api/v1/tickets/{id}
    public function update(Request $request, string $id): string
    {
        if (!$this->isEmployee()) {
            return $this->forbidden('Only staff can update tickets.');
        }

        $ticket = Ticket::find((int)$id);
        if (!$ticket) return $this->notFound();

        $data = $this->validate($request->all(), [
            'subject'     => 'required|min_length:5|max_length:255',
            'description' => 'required|min_length:10',
            'priority'    => 'required|in:low,medium,high,critical',
            'category_id' => 'nullable|integer',
        ]);

        $result = $this->ticketService->update((int)$id, $data, $this->userId());

        if (!$result['success']) return $this->error($result['message']);

        return $this->success(
            $this->formatTicket(Ticket::findWithDetails((int)$id)),
            $result['message']
        );
    }

    // DELETE /api/v1/tickets/{id}
    public function destroy(Request $request, string $id): string
    {
        if (!$this->isAdmin()) {
            return $this->forbidden('Only admins can delete tickets.');
        }

        $result = $this->ticketService->delete((int)$id, $this->userId());

        if (!$result['success']) return $this->error($result['message']);

        return $this->success(null, $result['message']);
    }

    // POST /api/v1/tickets/{id}/status
    public function changeStatus(Request $request, string $id): string
    {
        $status = (string)$request->input('status', '');
        $note   = (string)$request->input('note', '');

        if (!array_key_exists($status, Ticket::STATUSES)) {
            return $this->error('Invalid status value.');
        }

        $result = $this->ticketService->changeStatus(
            (int)$id, $status, $this->userRole(), $this->userId(), $note
        );

        if (!$result['success']) return $this->error($result['message'], 422);

        return $this->success($result, $result['message']);
    }

    // POST /api/v1/tickets/{id}/assign
    public function assign(Request $request, string $id): string
    {
        if (!$this->isEmployee()) {
            return $this->forbidden('Only staff can assign tickets.');
        }

        $assigneeId = (int)$request->input('assigned_to', 0);
        if (!$assigneeId) return $this->error('assigned_to is required.');

        $result = $this->ticketService->assign((int)$id, $assigneeId, $this->userId());

        if (!$result['success']) return $this->error($result['message'], 422);

        return $this->success($result, $result['message']);
    }

    // POST /api/v1/tickets/{id}/reply
    public function reply(Request $request, string $id): string
    {
        $message    = (string)$request->input('message', '');
        $isInternal = (bool)$request->input('is_internal', false) && $this->isEmployee();

        if (empty(trim($message))) {
            return $this->error('message is required.');
        }

        $result = $this->ticketService->addReply(
            (int)$id, $this->userId(), $message, $isInternal
        );

        if (!$result['success']) return $this->error($result['message'], 422);

        return $this->created(
            $this->formatConversation($result['conversation']),
            $result['message']
        );
    }

    // GET /api/v1/tickets/{id}/history
    public function history(Request $request, string $id): string
    {
        $ticket = Ticket::find((int)$id);
        if (!$ticket) return $this->notFound();

        if ($this->isClient() && (int)$ticket['created_by'] !== $this->userId()) {
            return $this->forbidden();
        }

        return $this->success(Ticket::getStatusHistory((int)$id));
    }

    // ── Formatters ────────────────────────────────────────────────

    private function formatTicket(?array $t): ?array
    {
        if (!$t) return null;
        return [
            'id'             => (int)$t['id'],
            'ticket_number'  => $t['ticket_number'],
            'subject'        => $t['subject'],
            'description'    => $t['description'],
            'status'         => $t['status'],
            'status_label'   => Ticket::STATUSES[$t['status']] ?? $t['status'],
            'priority'       => $t['priority'],
            'priority_label' => ucfirst($t['priority']),
            'category'       => $t['category_name'] ?? null,
            'company'        => $t['company_name']  ?? null,
            'creator'        => [
                'id'   => (int)$t['created_by'],
                'name' => trim(($t['creator_first'] ?? '') . ' ' . ($t['creator_last'] ?? '')),
            ],
            'assignee' => $t['assigned_to'] ? [
                'id'   => (int)$t['assigned_to'],
                'name' => trim(($t['assignee_first'] ?? '') . ' ' . ($t['assignee_last'] ?? '')),
            ] : null,
            'due_date'    => $t['due_date'],
            'resolved_at' => $t['resolved_at'],
            'closed_at'   => $t['closed_at'],
            'created_at'  => $t['created_at'],
            'updated_at'  => $t['updated_at'],
        ];
    }

    private function formatConversation(?array $c): ?array
    {
        if (!$c) return null;
        return [
            'id'          => (int)$c['id'],
            'message'     => $c['message'],
            'is_internal' => (bool)$c['is_internal'],
            'author'      => [
                'id'   => (int)$c['user_id'],
                'name' => trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')),
                'role' => $c['user_role'] ?? null,
            ],
            'attachments' => $c['attachments'] ?? [],
            'created_at'  => $c['created_at'],
        ];
    }
}
