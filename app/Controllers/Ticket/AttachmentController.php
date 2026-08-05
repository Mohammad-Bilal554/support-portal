<?php
declare(strict_types=1);
namespace App\Controllers\Ticket;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

class AttachmentController extends Controller
{
    // GET /tickets/attachments/{id}/download
    public function download(Request $request, string $attachId): string
    {
        $this->requireLogin();
        $user = $this->auth();
        $db   = Database::getInstance();

        $attachment = $db->fetchOne(
            "SELECT a.*, t.created_by, t.company_id
             FROM ticket_attachments a
             JOIN tickets t ON t.id = a.ticket_id
             WHERE a.id = ?",
            [(int)$attachId]
        );

        if (!$attachment) {
            $this->abort(404, 'Attachment not found.');
        }

        // Access control
        if ($user['role'] === 'client' && (int)$attachment['created_by'] !== (int)$user['id']) {
            $this->abort(403, 'Access denied.');
        }

        $filePath = base_path('public/assets/uploads/tickets/' . $attachment['stored_name']);

        if (!file_exists($filePath)) {
            $this->abort(404, 'File not found on disk.');
        }

        // Stream file
        $mimeType = $attachment['mime_type'] ?: mime_content_type($filePath) ?: 'application/octet-stream';
        $fileName = $attachment['original_name'];
        $fileSize = filesize($filePath);

        header('Content-Type: '        . $mimeType);
        header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
        header('Content-Length: '      . $fileSize);
        header('Cache-Control: private, no-cache');
        header('Pragma: no-cache');

        readfile($filePath);
        exit;
    }

    // DELETE /tickets/attachments/{id}
    public function destroy(Request $request, string $attachId): string
    {
        $this->requireLogin();
        $this->authorize($this->isEmployee());

        $db         = Database::getInstance();
        $attachment = $db->fetchOne(
            "SELECT * FROM ticket_attachments WHERE id = ?",
            [(int)$attachId]
        );

        if (!$attachment) {
            return $this->json(['success' => false, 'message' => 'Attachment not found.'], 404);
        }

        $filePath = base_path('public/assets/uploads/tickets/' . $attachment['stored_name']);
        if (file_exists($filePath)) unlink($filePath);

        $db->delete('ticket_attachments', ['id' => (int)$attachId]);

        return $this->json(['success' => true, 'message' => 'Attachment deleted.']);
    }

    // POST /tickets/{id}/attachments
    public function upload(Request $request, string $id): string
    {
        $this->requireLogin();

        if (!$request->hasFile('attachments')) {
            return $this->json(['success' => false, 'message' => 'No file uploaded.'], 422);
        }

        $service = new \App\Services\TicketService();
        $result  = $service->addReply(
            (int)$id,
            (int)$this->authId(),
            '',
            false,
            $request->file('attachments')
        );

        return $this->json($result);
    }
}
