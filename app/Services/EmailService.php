<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Logger;
use App\Core\Database;

/**
 * EmailService
 *
 * Handles all outgoing emails via PHPMailer (SMTP)
 * with a PHP mail() fallback. Supports queuing,
 * template rendering, and full notification history.
 */
class EmailService
{
    private Logger   $logger;
    private Database $db;
    private bool     $enabled;

    // Notification type constants
    public const NOTIF_NEW_TICKET      = 'new_ticket';
    public const NOTIF_TICKET_ASSIGNED = 'ticket_assigned';
    public const NOTIF_TICKET_REPLIED  = 'ticket_replied';
    public const NOTIF_STATUS_CHANGED  = 'status_changed';
    public const NOTIF_TICKET_RESOLVED = 'ticket_resolved';
    public const NOTIF_TICKET_CLOSED   = 'ticket_closed';
    public const NOTIF_WELCOME         = 'welcome';
    public const NOTIF_PASSWORD_RESET  = 'password_reset';

    public function __construct()
    {
        $this->logger  = Logger::getInstance();
        $this->db      = Database::getInstance();
        $this->enabled = $this->isEmailEnabled();
    }

    // ── Public notification methods ───────────────────────────────

    /**
     * Send new ticket notification to all admins + assigned employee.
     */
    public function notifyNewTicket(array $ticket, array $creator): void
    {
        if (!$this->shouldSend('notify_new_ticket')) return;

        // Notify admins
        $admins = $this->db->fetchAll(
            "SELECT * FROM users WHERE role = 'super_admin' AND is_active = 1"
        );
        foreach ($admins as $admin) {
            // Don't notify if admin created the ticket themselves
            if ((int)$admin['id'] === (int)$creator['id']) continue;
            $this->send(
                $admin['email'],
                $this->fullName($admin),
                "[New Ticket] {$ticket['ticket_number']}: {$ticket['subject']}",
                $this->renderTemplate('new-ticket', [
                    'ticket'     => $ticket,
                    'creator'    => $creator,
                    'recipientName' => $this->fullName($admin),
                    'ticketUrl'  => url('tickets/' . $ticket['id']),
                    'appName'    => env('APP_NAME', 'Support Portal'),
                ])
            );
        }

        // Save in-app notification for admins
        $this->createInAppNotification(
            array_column($admins, 'id'),
            self::NOTIF_NEW_TICKET,
            "New Ticket: {$ticket['ticket_number']}",
            "{$this->fullName($creator)} submitted: {$ticket['subject']}",
            ['ticket_id' => $ticket['id'], 'ticket_number' => $ticket['ticket_number']]
        );
    }

    /**
     * Notify assigned employee when ticket is assigned to them.
     */
    public function notifyTicketAssigned(array $ticket, array $assignee, array $assignedBy): void
    {
        if (!$this->shouldSend('notify_ticket_assigned')) return;
        if ((int)$assignee['id'] === (int)$assignedBy['id']) return;

        $this->send(
            $assignee['email'],
            $this->fullName($assignee),
            "[Assigned] {$ticket['ticket_number']}: {$ticket['subject']}",
            $this->renderTemplate('ticket-assigned', [
                'ticket'        => $ticket,
                'assignee'      => $assignee,
                'assignedBy'    => $assignedBy,
                'recipientName' => $this->fullName($assignee),
                'ticketUrl'     => url('tickets/' . $ticket['id']),
                'appName'       => env('APP_NAME', 'Support Portal'),
            ])
        );

        $this->createInAppNotification(
            [$assignee['id']],
            self::NOTIF_TICKET_ASSIGNED,
            "Ticket Assigned: {$ticket['ticket_number']}",
            "Assigned to you by {$this->fullName($assignedBy)}",
            ['ticket_id' => $ticket['id']]
        );
    }

    /**
     * Notify relevant parties when a reply is added.
     */
    public function notifyTicketReply(array $ticket, array $replier, string $message, bool $isInternal): void
    {
        if ($isInternal) return; // Internal notes don't trigger email

        $recipients = $this->getTicketRecipients($ticket, (int)$replier['id']);

        foreach ($recipients as $recipient) {
            $this->send(
                $recipient['email'],
                $this->fullName($recipient),
                "[Reply] {$ticket['ticket_number']}: {$ticket['subject']}",
                $this->renderTemplate('ticket-reply', [
                    'ticket'        => $ticket,
                    'replier'       => $replier,
                    'message'       => $message,
                    'recipientName' => $this->fullName($recipient),
                    'ticketUrl'     => url('tickets/' . $ticket['id']),
                    'appName'       => env('APP_NAME', 'Support Portal'),
                ])
            );
        }

        $recipientIds = array_column($recipients, 'id');
        if (!empty($recipientIds)) {
            $this->createInAppNotification(
                $recipientIds,
                self::NOTIF_TICKET_REPLIED,
                "New Reply: {$ticket['ticket_number']}",
                "{$this->fullName($replier)} replied to: {$ticket['subject']}",
                ['ticket_id' => $ticket['id']]
            );
        }
    }

    /**
     * Notify client and admin when ticket is resolved.
     */
    public function notifyTicketResolved(array $ticket, array $resolvedBy): void
    {
        if (!$this->shouldSend('notify_ticket_resolved')) return;

        // Notify ticket creator (client)
        $creator = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$ticket['created_by']]);
        if ($creator && (int)$creator['id'] !== (int)$resolvedBy['id']) {
            $this->send(
                $creator['email'],
                $this->fullName($creator),
                "[Resolved] {$ticket['ticket_number']}: {$ticket['subject']}",
                $this->renderTemplate('ticket-resolved', [
                    'ticket'        => $ticket,
                    'resolvedBy'    => $resolvedBy,
                    'recipientName' => $this->fullName($creator),
                    'ticketUrl'     => url('tickets/' . $ticket['id']),
                    'appName'       => env('APP_NAME', 'Support Portal'),
                ])
            );

            $this->createInAppNotification(
                [$creator['id']],
                self::NOTIF_TICKET_RESOLVED,
                "Ticket Resolved: {$ticket['ticket_number']}",
                "Your ticket has been resolved by {$this->fullName($resolvedBy)}",
                ['ticket_id' => $ticket['id']]
            );
        }
    }

    /**
     * Notify when status changes (general).
     */
    public function notifyStatusChanged(array $ticket, string $oldStatus, string $newStatus, array $changedBy): void
    {
        // Handled specifically for resolved/assigned — skip generic for those
        if (in_array($newStatus, ['resolved', 'assigned'])) return;

        $recipients = $this->getTicketRecipients($ticket, (int)$changedBy['id']);
        $statusLabel = ucwords(str_replace('_', ' ', $newStatus));

        foreach ($recipients as $recipient) {
            $this->send(
                $recipient['email'],
                $this->fullName($recipient),
                "[Status Update] {$ticket['ticket_number']}: now {$statusLabel}",
                $this->renderTemplate('status-changed', [
                    'ticket'        => $ticket,
                    'oldStatus'     => ucwords(str_replace('_', ' ', $oldStatus)),
                    'newStatus'     => $statusLabel,
                    'changedBy'     => $changedBy,
                    'recipientName' => $this->fullName($recipient),
                    'ticketUrl'     => url('tickets/' . $ticket['id']),
                    'appName'       => env('APP_NAME', 'Support Portal'),
                ])
            );
        }
    }

    /**
     * Send welcome email to new user.
     */
    public function notifyWelcome(array $user, string $temporaryPassword = ''): void
    {
        $this->send(
            $user['email'],
            $this->fullName($user),
            "Welcome to " . env('APP_NAME', 'Support Portal'),
            $this->renderTemplate('welcome', [
                'user'              => $user,
                'recipientName'     => $this->fullName($user),
                'temporaryPassword' => $temporaryPassword,
                'loginUrl'          => url('auth/login'),
                'appName'           => env('APP_NAME', 'Support Portal'),
                'role'              => ucfirst(str_replace('_', ' ', $user['role'])),
            ])
        );
    }

    /**
     * Send password reset email.
     */
    public function sendPasswordReset(string $toEmail, string $toName, string $resetUrl): bool
    {
        return $this->send(
            $toEmail,
            $toName,
            "Reset Your Password — " . env('APP_NAME', 'Support Portal'),
            $this->renderTemplate('password-reset', [
                'name'         => $toName,
                'resetUrl'     => $resetUrl,
                'expiresIn'    => '1 hour',
                'appName'      => env('APP_NAME', 'Support Portal'),
            ])
        );
    }

    // ── Core send method ──────────────────────────────────────────

    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
        array  $attachments = []
    ): bool {
        if (!$this->enabled) {
            $this->logger->info("Email skipped (disabled): {$toEmail} — {$subject}");
            return true;
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->logger->warning("Invalid email address: {$toEmail}");
            return false;
        }

        try {
            if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
                return $this->sendWithPhpMailer($toEmail, $toName, $subject, $body, $attachments);
            }
            return $this->sendWithMail($toEmail, $toName, $subject, $body);
        } catch (\Throwable $e) {
            $this->logger->error("Email failed [{$toEmail}]: " . $e->getMessage());
            return false;
        }
    }

    // ── PHPMailer driver ──────────────────────────────────────────

    private function sendWithPhpMailer(
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
        array  $attachments = []
    ): bool {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        $driver = env('MAIL_DRIVER', 'smtp');

        if ($driver === 'smtp') {
            $mail->isSMTP();
            $mail->Host       = env('MAIL_HOST', 'smtp.mailtrap.io');
            $mail->SMTPAuth   = true;
            $mail->Username   = env('MAIL_USERNAME', '');
            $mail->Password   = env('MAIL_PASSWORD', '');
            $mail->Port       = (int) env('MAIL_PORT', 2525);
            $mail->SMTPSecure = match (strtolower(env('MAIL_ENCRYPTION', 'tls'))) {
                'ssl'   => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
                'tls'   => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
                default => '',
            };
        } elseif ($driver === 'sendmail') {
            $mail->isSendmail();
        } else {
            $mail->isMail();
        }

        $mail->setFrom(
            env('MAIL_FROM_ADDRESS', 'noreply@support-portal.com'),
            env('MAIL_FROM_NAME',    'Support Portal')
        );
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $this->htmlToText($body);

        foreach ($attachments as $att) {
            if (!empty($att['path']) && file_exists($att['path'])) {
                $mail->addAttachment($att['path'], $att['name'] ?? basename($att['path']));
            }
        }

        $mail->send();
        $this->logger->info("Email sent [{$driver}]: {$toEmail} — {$subject}");
        return true;
    }

    // ── PHP mail() fallback ───────────────────────────────────────

    private function sendWithMail(string $toEmail, string $toName, string $subject, string $body): bool
    {
        $from    = env('MAIL_FROM_ADDRESS', 'noreply@support-portal.com');
        $fromName = env('MAIL_FROM_NAME',   'Support Portal');

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        $result = mail("{$toName} <{$toEmail}>", $subject, $body, $headers);

        if ($result) {
            $this->logger->info("Email sent [mail()]: {$toEmail} — {$subject}");
        } else {
            $this->logger->error("Email failed [mail()]: {$toEmail} — {$subject}");
        }

        return $result;
    }

    // ── Template renderer ─────────────────────────────────────────

    public function renderTemplate(string $name, array $data = []): string
    {
        $path = base_path("resources/views/emails/{$name}.php");

        if (file_exists($path)) {
            extract($data, EXTR_SKIP);
            ob_start();
            include $path;
            $content = ob_get_clean();
            return $this->wrapInLayout($content, $data['appName'] ?? env('APP_NAME', 'Support Portal'));
        }

        // Inline fallback
        return $this->inlineFallback($name, $data);
    }

    private function wrapInLayout(string $content, string $appName): string
    {
        $year    = date('Y');
        $primary = '#0d6efd';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{$appName}</title>
<!--[if mso]>
<noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
<![endif]-->
<style>
  body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;}
  table,td{mso-table-lspace:0pt;mso-table-rspace:0pt;}
  img{-ms-interpolation-mode:bicubic;border:0;outline:none;text-decoration:none;}
  body{margin:0!important;padding:0!important;background:#f1f5f9;}
  @media screen and (max-width:600px){
    .email-wrapper{width:100%!important;padding:0!important;}
    .email-card{border-radius:0!important;}
    .btn-cta{width:100%!important;display:block!important;}
  }
</style>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px;">
  <tr><td align="center">
    <table role="presentation" class="email-wrapper" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

      <!-- Header -->
      <tr><td>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
               class="email-card"
               style="background:linear-gradient(135deg,{$primary},#0a58ca);border-radius:16px 16px 0 0;overflow:hidden;">
          <tr>
            <td style="padding:28px 32px;text-align:center;">
              <span style="font-size:1.3rem;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">
                🎧 {$appName}
              </span>
            </td>
          </tr>
        </table>
      </td></tr>

      <!-- Body -->
      <tr><td>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
               style="background:#ffffff;padding:36px 40px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
          <tr><td style="color:#374151;font-size:15px;line-height:1.7;">
            {$content}
          </td></tr>
        </table>
      </td></tr>

      <!-- Footer -->
      <tr><td>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
               style="background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 16px 16px;padding:20px 32px;text-align:center;">
          <tr><td style="color:#94a3b8;font-size:12px;line-height:1.6;">
            <p style="margin:0 0 4px;">This email was sent by <strong>{$appName}</strong>. Please do not reply directly.</p>
            <p style="margin:0;">&copy; {$year} {$appName}. All rights reserved.</p>
          </td></tr>
        </table>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }

    private function inlineFallback(string $name, array $data): string
    {
        $appName = $data['appName'] ?? env('APP_NAME', 'Support Portal');
        $body    = "<p>Hello {$data['recipientName']},</p><p>You have a new notification from {$appName}.</p>";
        return $this->wrapInLayout($body, $appName);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function getTicketRecipients(array $ticket, int $excludeUserId): array
    {
        $ids = [];

        // Creator
        if ($ticket['created_by']) $ids[] = (int)$ticket['created_by'];

        // Assigned employee
        if ($ticket['assigned_to']) $ids[] = (int)$ticket['assigned_to'];

        // Remove the person who triggered the action
        $ids = array_unique(array_filter($ids, fn($id) => $id !== $excludeUserId));

        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return $this->db->fetchAll(
            "SELECT * FROM users WHERE id IN ({$placeholders}) AND is_active = 1",
            array_values($ids)
        );
    }

    private function createInAppNotification(
        array  $userIds,
        string $type,
        string $title,
        string $message,
        array  $data = []
    ): void {
        foreach ($userIds as $userId) {
            if (!$userId) continue;
            try {
                $this->db->insert('notifications', [
                    'user_id'    => (int)$userId,
                    'type'       => $type,
                    'title'      => $title,
                    'message'    => $message,
                    'data'       => json_encode($data),
                    'is_read'    => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('In-app notification failed: ' . $e->getMessage());
            }
        }
    }

    private function shouldSend(string $settingKey): bool
    {
        if (!$this->enabled) return false;
        try {
            $val = $this->db->fetchColumn(
                "SELECT value FROM settings WHERE key_name = ?", [$settingKey]
            );
            return $val === null || (bool)$val;
        } catch (\Throwable) {
            return true;
        }
    }

    private function isEmailEnabled(): bool
    {
        try {
            $val = $this->db->fetchColumn(
                "SELECT value FROM settings WHERE key_name = 'email_notifications'"
            );
            return $val === null || (bool)$val;
        } catch (\Throwable) {
            return (bool)env('MAIL_USERNAME');
        }
    }

    private function fullName(array $user): string
    {
        return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
            ?: ($user['email'] ?? 'User');
    }

    private function htmlToText(string $html): string
    {
        $text = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</tr>'], "\n", $html));
        return trim(preg_replace('/\n{3,}/', "\n\n", $text));
    }
}
