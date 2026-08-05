<?php
declare(strict_types=1);
namespace App\Controllers\Ticket;

use App\Core\Controller;
use App\Core\Request;
use App\Models\User;
use App\Services\TicketService;

class ConversationController extends Controller
{
    private TicketService $ticketService;

    public function __construct()
    {
        parent::__construct();
        $this->ticketService = new TicketService();
    }

    // POST /tickets/{id}/reply
    public function reply(Request $request, string $id): string
    {
        $this->requireLogin();
        $user = $this->auth();

        $message    = trim((string)$request->input('message', ''));
        $isInternal = $request->boolean('is_internal') && in_array($user['role'], ['super_admin','employee']);
        $files      = $request->hasFile('attachments') ? $request->file('attachments') : [];

        $result = $this->ticketService->addReply(
            (int)$id,
            (int)$user['id'],
            $message,
            $isInternal,
            $files
        );

        if (!$result['success']) {
            if ($this->isAjax()) {
                return $this->json($result, 422);
            }
            $this->session->setFlash('error', $result['message']);
            $this->redirect(url("tickets/{$id}"));
        }

        if ($this->isAjax()) {
            $conv    = $result['conversation'];
            $convHtml = $this->renderConversationItem($conv, $user);
            return $this->json([
                'success' => true,
                'message' => 'Reply added.',
                'html'    => $convHtml,
            ]);
        }

        $this->session->success('Reply added successfully.');
        $this->redirect(url("tickets/{$id}") . '#reply-' . ($result['conversation']['id'] ?? ''));
    }

    // DELETE /tickets/conversations/{convId}
    public function destroy(Request $request, string $convId): string
    {
        $this->requireLogin();
        $this->authorize($this->isEmployee());

        $db   = \App\Core\Database::getInstance();
        $conv = $db->fetchOne("SELECT * FROM ticket_conversations WHERE id = ?", [(int)$convId]);

        if (!$conv) {
            return $this->json(['success' => false, 'message' => 'Reply not found.'], 404);
        }

        // Only author or admin can delete
        if ((int)$conv['user_id'] !== $this->authId() && !$this->isAdmin()) {
            return $this->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        // Delete attachments
        $attachments = $db->fetchAll(
            "SELECT stored_name FROM ticket_attachments WHERE conversation_id = ?",
            [(int)$convId]
        );
        foreach ($attachments as $a) {
            $path = base_path('public/assets/uploads/tickets/' . $a['stored_name']);
            if (file_exists($path)) unlink($path);
        }

        $db->delete('ticket_conversations', ['id' => (int)$convId]);

        return $this->json(['success' => true, 'message' => 'Reply deleted.']);
    }

    // ── Render a single conversation item as HTML ─────────────────

    private function renderConversationItem(array $conv, array $currentUser): string
    {
        $name       = htmlspecialchars(trim(($conv['first_name'] ?? '') . ' ' . ($conv['last_name'] ?? '')));
        $message    = nl2br(htmlspecialchars($conv['message']));
        $time       = time_ago($conv['created_at']);
        $isInternal = (bool)$conv['is_internal'];
        $isOwn      = (int)$conv['user_id'] === (int)$currentUser['id'];
        $avatar     = \App\Models\User::avatarUrl($conv + ['first_name' => $conv['first_name'] ?? '', 'last_name' => $conv['last_name'] ?? '']);

        $internalBadge = $isInternal
            ? '<span class="badge bg-warning text-dark ms-2" style="font-size:.65rem;">Internal Note</span>'
            : '';

        $deleteBtn = $isOwn || in_array($currentUser['role'], ['super_admin'])
            ? "<button class='btn btn-sm btn-icon text-danger ms-auto'
                       onclick='deleteReply({$conv['id']}, this)'
                       title='Delete reply'><i class='bi bi-trash'></i></button>"
            : '';

        return "
        <div class='timeline-item fade-in-up' id='reply-{$conv['id']}'>
            <div class='timeline-icon " . ($isInternal ? 'bg-warning' : ($isOwn ? 'bg-primary' : 'bg-secondary')) . " bg-opacity-10'>
                <img src='{$avatar}' width='36' height='36' style='border-radius:50%;object-fit:cover;'>
            </div>
            <div class='timeline-content " . ($isInternal ? 'internal' : '') . "'>
                <div class='timeline-meta'>
                    <div class='d-flex align-items-center gap-2 flex-wrap'>
                        <span class='timeline-author'>{$name}</span>
                        {$internalBadge}
                        <span class='timeline-time'>{$time}</span>
                    </div>
                    {$deleteBtn}
                </div>
                <div style='font-size:.875rem;line-height:1.7;'>{$message}</div>
            </div>
        </div>";
    }
}
