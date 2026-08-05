<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Ticket;
use App\Models\User;

class TicketService
{
    private Database $db;
    private Logger   $logger;

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->logger = Logger::getInstance();
    }

    // ── Create ticket ─────────────────────────────────────────────

    public function create(array $data, int $creatorId, ?array $files = []): array
    {
        $ticketNumber = Ticket::generateNumber();

        $insertData = [
            'ticket_number' => $ticketNumber,
            'subject'       => trim($data['subject']),
            'description'   => trim($data['description']),
            'status'        => 'open',
            'priority'      => $data['priority'] ?? 'medium',
            'category_id'   => !empty($data['category_id'])  ? (int)$data['category_id']  : null,
            'company_id'    => !empty($data['company_id'])   ? (int)$data['company_id']   : null,
            'created_by'    => $creatorId,
            'assigned_to'   => !empty($data['assigned_to'])  ? (int)$data['assigned_to']  : null,
            'due_date'      => !empty($data['due_date'])      ? $data['due_date']           : null,
        ];

        // Auto-assign status if assignee set
        if ($insertData['assigned_to']) {
            $insertData['status'] = 'assigned';
        }

        $ticketId = (int)Ticket::create($insertData);

        // Handle file attachments
        if (!empty($files)) {
            $this->handleAttachments($files, $ticketId, $creatorId);
        }

        // Log status history
        $this->logStatusHistory($ticketId, $creatorId, null, $insertData['status'], 'Ticket created');

        // Log activity
        $this->logActivity($creatorId, 'ticket_created', 'ticket', $ticketId,
            "Ticket created: {$ticketNumber} — {$insertData['subject']}");

        $this->logger->info("Ticket created: #{$ticketId} {$ticketNumber}");

        return [
            'success'   => true,
            'message'   => 'Ticket created successfully.',
            'ticket_id' => $ticketId,
            'ticket_number' => $ticketNumber,
        ];
    }

    // ── Update ticket ─────────────────────────────────────────────

    public function update(int $ticketId, array $data, int $userId): array
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket not found.'];
        }

        $updateData = array_filter([
            'subject'     => !empty($data['subject'])      ? trim($data['subject'])   : null,
            'description' => !empty($data['description'])  ? trim($data['description']) : null,
            'priority'    => $data['priority']     ?? null,
            'category_id' => !empty($data['category_id']) ? (int)$data['category_id'] : null,
            'due_date'    => $data['due_date']     ?? null,
        ], fn($v) => $v !== null);

        Ticket::updateById($ticketId, $updateData);

        $this->logActivity($userId, 'ticket_updated', 'ticket', $ticketId,
            "Ticket updated: #{$ticketId}");

        return ['success' => true, 'message' => 'Ticket updated successfully.'];
    }

    // ── Change status ─────────────────────────────────────────────

    public function changeStatus(int $ticketId, string $newStatus, string $role, int $userId, string $note = ''): array
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket not found.'];
        }

        $oldStatus = $ticket['status'];

        if (!Ticket::canTransition($oldStatus, $newStatus, $role)) {
            return ['success' => false, 'message' => "Cannot change status from '{$oldStatus}' to '{$newStatus}'."];
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'resolved') {
            $updateData['resolved_at'] = date('Y-m-d H:i:s');
        } elseif ($newStatus === 'closed') {
            $updateData['closed_at'] = date('Y-m-d H:i:s');
        }

        Ticket::updateById($ticketId, $updateData);

        $this->logStatusHistory($ticketId, $userId, $oldStatus, $newStatus, $note);
        $this->logActivity($userId, 'status_changed', 'ticket', $ticketId,
            "Status changed: {$oldStatus} → {$newStatus}");

        return [
            'success'    => true,
            'message'    => 'Status updated successfully.',
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ];
    }

    // ── Assign ticket ─────────────────────────────────────────────

    public function assign(int $ticketId, int $assigneeId, int $assignedById): array
    {
        $ticket   = Ticket::find($ticketId);
        $assignee = User::find($assigneeId);

        if (!$ticket) return ['success' => false, 'message' => 'Ticket not found.'];
        if (!$assignee) return ['success' => false, 'message' => 'Assignee not found.'];

        $oldStatus = $ticket['status'];
        $newStatus = in_array($oldStatus, ['open']) ? 'assigned' : $oldStatus;

        Ticket::updateById($ticketId, [
            'assigned_to' => $assigneeId,
            'status'      => $newStatus,
        ]);

        if ($oldStatus !== $newStatus) {
            $this->logStatusHistory($ticketId, $assignedById, $oldStatus, $newStatus,
                'Assigned to ' . User::fullName($assignee));
        }

        $this->logActivity($assignedById, 'ticket_assigned', 'ticket', $ticketId,
            "Ticket assigned to: " . User::fullName($assignee));

        return [
            'success'  => true,
            'message'  => 'Ticket assigned to ' . User::fullName($assignee) . '.',
            'assignee' => User::fullName($assignee),
        ];
    }

    // ── Add reply ─────────────────────────────────────────────────

    public function addReply(
        int    $ticketId,
        int    $userId,
        string $message,
        bool   $isInternal = false,
        array  $files      = []
    ): array {
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket not found.'];
        }

        if (empty(trim($message)) && empty($files)) {
            return ['success' => false, 'message' => 'Reply cannot be empty.'];
        }

        $convId = (int)$this->db->insert('ticket_conversations', [
            'ticket_id'   => $ticketId,
            'user_id'     => $userId,
            'message'     => trim($message),
            'is_internal' => $isInternal ? 1 : 0,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        // Attachments for this reply
        if (!empty($files)) {
            $this->handleAttachments($files, $ticketId, $userId, $convId);
        }

        // Update ticket updated_at
        Ticket::updateById($ticketId, ['updated_at' => date('Y-m-d H:i:s')]);

        // If client replies and ticket is waiting_for_client → back to in_progress
        $user = User::find($userId);
        if ($user && $user['role'] === 'client' && $ticket['status'] === 'waiting_for_client') {
            Ticket::updateById($ticketId, ['status' => 'in_progress']);
            $this->logStatusHistory($ticketId, $userId, 'waiting_for_client', 'in_progress', 'Client replied');
        }

        $this->logActivity($userId, 'reply_added', 'ticket', $ticketId,
            $isInternal ? 'Internal note added' : 'Reply added to ticket');

        // Fetch full conversation row for response
        $conv = $this->db->fetchOne(
            "SELECT c.*, u.first_name, u.last_name, u.avatar, u.role AS user_role
             FROM ticket_conversations c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.id = ?",
            [$convId]
        );

        return [
            'success'      => true,
            'message'      => 'Reply added successfully.',
            'conversation' => $conv,
        ];
    }

    // ── Delete ticket ─────────────────────────────────────────────

    public function delete(int $ticketId, int $userId): array
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket not found.'];
        }

        // Delete all attachments from disk
        $attachments = $this->db->fetchAll(
            "SELECT stored_name FROM ticket_attachments WHERE ticket_id = ?",
            [$ticketId]
        );
        foreach ($attachments as $a) {
            $path = base_path('public/assets/uploads/tickets/' . $a['stored_name']);
            if (file_exists($path)) unlink($path);
        }

        Ticket::deleteById($ticketId);

        $this->logActivity($userId, 'ticket_deleted', 'ticket', $ticketId,
            "Ticket deleted: {$ticket['ticket_number']}");

        return ['success' => true, 'message' => 'Ticket deleted successfully.'];
    }

    // ── Attachments ───────────────────────────────────────────────

    private function handleAttachments(array $files, int $ticketId, int $userId, ?int $convId = null): void
    {
        $allowed  = ['pdf','doc','docx','xls','xlsx','png','jpg','jpeg','gif','zip','txt','csv'];
        $maxSize  = (int)env('UPLOAD_MAX_SIZE', 10485760);
        $uploadDir = base_path('public/assets/uploads/tickets');

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // Normalise single/multiple file upload structure
        $fileList = [];
        if (isset($files['name']) && is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $fileList[] = [
                        'name'     => $files['name'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'type'     => $files['type'][$i],
                        'size'     => $files['size'][$i],
                        'error'    => $files['error'][$i],
                    ];
                }
            }
        } elseif (isset($files['error']) && $files['error'] === UPLOAD_ERR_OK) {
            $fileList[] = $files;
        }

        foreach ($fileList as $file) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) continue;
            if ($file['size'] > $maxSize)  continue;

            $storedName = uniqid('attach_', true) . '.' . $ext;
            $dest       = $uploadDir . '/' . $storedName;

            if (!move_uploaded_file($file['tmp_name'], $dest)) continue;

            $this->db->insert('ticket_attachments', [
                'ticket_id'       => $ticketId,
                'conversation_id' => $convId,
                'user_id'         => $userId,
                'original_name'   => $file['name'],
                'stored_name'     => $storedName,
                'mime_type'       => $file['type'],
                'file_size'       => $file['size'],
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function logStatusHistory(int $ticketId, int $userId, ?string $old, string $new, string $note = ''): void
    {
        $this->db->insert('ticket_status_history', [
            'ticket_id'  => $ticketId,
            'changed_by' => $userId,
            'old_status' => $old,
            'new_status' => $new,
            'note'       => $note,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function logActivity(int $userId, string $action, string $entityType, int $entityId, string $desc): void
    {
        try {
            $this->db->insert('activity_logs', [
                'user_id'     => $userId,
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'description' => $desc,
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Activity log failed: ' . $e->getMessage());
        }
    }
}
