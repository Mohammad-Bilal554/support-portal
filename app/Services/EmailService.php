<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * EmailService
 *
 * PHPMailer wrapper for sending all system emails.
 * Falls back to PHP mail() if PHPMailer not installed.
 */
class EmailService
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    // ----------------------------------------------------------------
    // Public send methods
    // ----------------------------------------------------------------

    public function sendPasswordReset(string $toEmail, string $toName, string $resetUrl): bool
    {
        $subject = 'Reset Your Password — ' . env('APP_NAME', 'Support Portal');

        $body = $this->renderTemplate('password-reset', [
            'name'      => $toName,
            'resetUrl'  => $resetUrl,
            'appName'   => env('APP_NAME', 'Support Portal'),
            'expiresIn' => '1 hour',
        ]);

        return $this->send($toEmail, $toName, $subject, $body);
    }

    public function sendWelcome(string $toEmail, string $toName, string $role): bool
    {
        $subject = 'Welcome to ' . env('APP_NAME', 'Support Portal');

        $body = $this->renderTemplate('welcome', [
            'name'    => $toName,
            'role'    => ucfirst($role),
            'appName' => env('APP_NAME', 'Support Portal'),
            'loginUrl'=> url('auth/login'),
        ]);

        return $this->send($toEmail, $toName, $subject, $body);
    }

    public function sendNewTicketNotification(
        string $toEmail,
        string $toName,
        array  $ticket
    ): bool {
        $subject = "[Ticket #{$ticket['ticket_number']}] New ticket: {$ticket['subject']}";

        $body = $this->renderTemplate('new-ticket', [
            'name'         => $toName,
            'ticket'       => $ticket,
            'ticketUrl'    => url('tickets/' . $ticket['id']),
            'appName'      => env('APP_NAME', 'Support Portal'),
        ]);

        return $this->send($toEmail, $toName, $subject, $body);
    }

    public function sendTicketAssigned(
        string $toEmail,
        string $toName,
        array  $ticket
    ): bool {
        $subject = "[Ticket #{$ticket['ticket_number']}] Assigned to you: {$ticket['subject']}";

        $body = $this->renderTemplate('ticket-assigned', [
            'name'      => $toName,
            'ticket'    => $ticket,
            'ticketUrl' => url('tickets/' . $ticket['id']),
            'appName'   => env('APP_NAME', 'Support Portal'),
        ]);

        return $this->send($toEmail, $toName, $subject, $body);
    }

    public function sendTicketResolved(
        string $toEmail,
        string $toName,
        array  $ticket
    ): bool {
        $subject = "[Ticket #{$ticket['ticket_number']}] Resolved: {$ticket['subject']}";

        $body = $this->renderTemplate('ticket-resolved', [
            'name'      => $toName,
            'ticket'    => $ticket,
            'ticketUrl' => url('tickets/' . $ticket['id']),
            'appName'   => env('APP_NAME', 'Support Portal'),
        ]);

        return $this->send($toEmail, $toName, $subject, $body);
    }

    public function sendTicketReply(
        string $toEmail,
        string $toName,
        array  $ticket,
        string $replyFrom
    ): bool {
        $subject = "[Ticket #{$ticket['ticket_number']}] New reply: {$ticket['subject']}";

        $body = $this->renderTemplate('ticket-reply', [
            'name'      => $toName,
            'ticket'    => $ticket,
            'replyFrom' => $replyFrom,
            'ticketUrl' => url('tickets/' . $ticket['id']),
            'appName'   => env('APP_NAME', 'Support Portal'),
        ]);

        return $this->send($toEmail, $toName, $subject, $body);
    }

    // ----------------------------------------------------------------
    // Core send method
    // ----------------------------------------------------------------

    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
        array  $attachments = []
    ): bool {
        try {
            if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
                return $this->sendWithPhpMailer($toEmail, $toName, $subject, $body, $attachments);
            }
            return $this->sendWithMail($toEmail, $toName, $subject, $body);
        } catch (\Throwable $e) {
            $this->logger->error("Email send failed to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }

    // ----------------------------------------------------------------
    // PHPMailer driver
    // ----------------------------------------------------------------

    private function sendWithPhpMailer(
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
        array  $attachments = []
    ): bool {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = env('MAIL_HOST', 'smtp.mailtrap.io');
        $mail->Port       = (int) env('MAIL_PORT', 2525);
        $mail->SMTPAuth   = true;
        $mail->Username   = env('MAIL_USERNAME', '');
        $mail->Password   = env('MAIL_PASSWORD', '');

        $encryption = strtolower(env('MAIL_ENCRYPTION', 'tls'));
        $mail->SMTPSecure = match ($encryption) {
            'ssl'   => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
            'tls'   => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
            default => '',
        };

        // Sender
        $mail->setFrom(
            env('MAIL_FROM_ADDRESS', 'noreply@support-portal.com'),
            env('MAIL_FROM_NAME',    'Support Portal')
        );

        // Recipient
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

        // Attachments
        foreach ($attachments as $attachment) {
            if (file_exists($attachment['path'])) {
                $mail->addAttachment($attachment['path'], $attachment['name'] ?? basename($attachment['path']));
            }
        }

        $mail->send();
        $this->logger->info("Email sent to {$toEmail}: {$subject}");
        return true;
    }

    // ----------------------------------------------------------------
    // PHP mail() fallback
    // ----------------------------------------------------------------

    private function sendWithMail(
        string $toEmail,
        string $toName,
        string $subject,
        string $body
    ): bool {
        $fromEmail = env('MAIL_FROM_ADDRESS', 'noreply@support-portal.com');
        $fromName  = env('MAIL_FROM_NAME', 'Support Portal');

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        $result = mail("{$toName} <{$toEmail}>", $subject, $body, $headers);

        if ($result) {
            $this->logger->info("Email sent (mail()) to {$toEmail}: {$subject}");
        } else {
            $this->logger->error("Email failed (mail()) to {$toEmail}: {$subject}");
        }

        return $result;
    }

    // ----------------------------------------------------------------
    // Template renderer
    // ----------------------------------------------------------------

    private function renderTemplate(string $name, array $data = []): string
    {
        $templatePath = base_path("resources/views/emails/{$name}.php");

        if (file_exists($templatePath)) {
            extract($data, EXTR_SKIP);
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }

        // Inline fallback template
        return $this->inlineFallback($name, $data);
    }

    private function inlineFallback(string $name, array $data): string
    {
        $appName = $data['appName'] ?? env('APP_NAME', 'Support Portal');
        $content = match ($name) {
            'password-reset' => "
                <p>Hello {$data['name']},</p>
                <p>Click the button below to reset your password. This link expires in {$data['expiresIn']}.</p>
                <p><a href='{$data['resetUrl']}' style='background:#0d6efd;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;display:inline-block;'>Reset Password</a></p>
                <p>If you did not request a password reset, please ignore this email.</p>
                <p>For security, do not share this link with anyone.</p>
            ",
            'welcome' => "
                <p>Hello {$data['name']},</p>
                <p>Welcome to {$appName}! Your account has been created with the role: <strong>{$data['role']}</strong>.</p>
                <p><a href='{$data['loginUrl']}' style='background:#0d6efd;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;display:inline-block;'>Login Now</a></p>
            ",
            default => "<p>Hello {$data['name']},</p><p>You have a new notification from {$appName}.</p>",
        };

        return $this->wrapInLayout($content, $appName);
    }

    private function wrapInLayout(string $content, string $appName): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
                .header { background: linear-gradient(135deg, #0d6efd, #0a58ca); color: #fff; padding: 28px 32px; text-align: center; }
                .header h1 { margin: 0; font-size: 1.4rem; font-weight: 600; letter-spacing: 0.5px; }
                .body { padding: 32px; color: #374151; line-height: 1.7; font-size: 0.95rem; }
                .footer { background: #f8fafc; padding: 16px 32px; text-align: center; color: #94a3b8; font-size: 0.8rem; border-top: 1px solid #e2e8f0; }
                a { color: #0d6efd; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header"><h1>{$appName}</h1></div>
                <div class="body">{$content}</div>
                <div class="footer">
                    <p>This is an automated message from {$appName}. Please do not reply directly to this email.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
