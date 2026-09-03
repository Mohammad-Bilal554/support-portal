<?php
/**
 * Email Test Page (visible only in local/dev environment)
 */
use App\Core\Session;
use App\Core\Csrf;
use App\Services\EmailService;

$session   = Session::getInstance();
$csrfToken = Csrf::getToken();
$title     = 'Email Test';

// Handle test send
$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_type'])) {
    if (!hash_equals($csrfToken, $_POST['_csrf_token'] ?? '')) {
        $testResult = ['success' => false, 'message' => 'CSRF mismatch.'];
    } else {
        $emailService = new EmailService();
        $toEmail = trim($_POST['to_email'] ?? '');
        $toName  = trim($_POST['to_name']  ?? 'Test User');

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $testResult = ['success' => false, 'message' => 'Invalid email address.'];
        } else {
            $type   = $_POST['test_type'];
            $result = false;

            $fakeTicket = [
                'id'            => 999,
                'ticket_number' => 'TKT-' . date('Y') . '-00001',
                'subject'       => 'Test Ticket Subject',
                'description'   => 'This is a test ticket description for email preview purposes.',
                'status'        => 'open',
                'priority'      => 'high',
                'category_name' => 'Technical Support',
                'category_color'=> '#0d6efd',
                'company_name'  => 'Acme Corp',
                'created_by'    => 1,
                'assigned_to'   => null,
                'due_date'      => date('Y-m-d', strtotime('+3 days')),
                'created_at'    => date('Y-m-d H:i:s'),
            ];
            $fakeUser = [
                'id'         => 1,
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'email'      => $toEmail,
                'role'       => 'employee',
            ];

            switch ($type) {
                case 'new_ticket':
                    $result = $emailService->send(
                        $toEmail, $toName,
                        "[New Ticket] {$fakeTicket['ticket_number']}: {$fakeTicket['subject']}",
                        $emailService->renderTemplate('new-ticket', [
                            'ticket'        => $fakeTicket,
                            'creator'       => $fakeUser,
                            'recipientName' => $toName,
                            'ticketUrl'     => url('tickets/1'),
                            'appName'       => env('APP_NAME', 'Support Portal'),
                        ])
                    );
                    break;

                case 'ticket_assigned':
                    $result = $emailService->send(
                        $toEmail, $toName,
                        "[Assigned] {$fakeTicket['ticket_number']}: {$fakeTicket['subject']}",
                        $emailService->renderTemplate('ticket-assigned', [
                            'ticket'        => $fakeTicket,
                            'assignee'      => $fakeUser,
                            'assignedBy'    => array_merge($fakeUser, ['first_name'=>'Admin','last_name'=>'User']),
                            'recipientName' => $toName,
                            'ticketUrl'     => url('tickets/1'),
                            'appName'       => env('APP_NAME', 'Support Portal'),
                        ])
                    );
                    break;

                case 'ticket_reply':
                    $result = $emailService->send(
                        $toEmail, $toName,
                        "[Reply] {$fakeTicket['ticket_number']}: {$fakeTicket['subject']}",
                        $emailService->renderTemplate('ticket-reply', [
                            'ticket'        => $fakeTicket,
                            'replier'       => $fakeUser,
                            'message'       => "Hello,\n\nThank you for contacting us. We've reviewed your issue and here is our response.\n\nThis is a test reply to verify the email template is working correctly.\n\nBest regards,\nSupport Team",
                            'recipientName' => $toName,
                            'ticketUrl'     => url('tickets/1'),
                            'appName'       => env('APP_NAME', 'Support Portal'),
                        ])
                    );
                    break;

                case 'ticket_resolved':
                    $result = $emailService->send(
                        $toEmail, $toName,
                        "[Resolved] {$fakeTicket['ticket_number']}: {$fakeTicket['subject']}",
                        $emailService->renderTemplate('ticket-resolved', [
                            'ticket'        => $fakeTicket,
                            'resolvedBy'    => $fakeUser,
                            'recipientName' => $toName,
                            'ticketUrl'     => url('tickets/1'),
                            'appName'       => env('APP_NAME', 'Support Portal'),
                        ])
                    );
                    break;

                case 'status_changed':
                    $result = $emailService->send(
                        $toEmail, $toName,
                        "[Status Update] {$fakeTicket['ticket_number']}: now In Progress",
                        $emailService->renderTemplate('status-changed', [
                            'ticket'        => $fakeTicket,
                            'oldStatus'     => 'Open',
                            'newStatus'     => 'In Progress',
                            'changedBy'     => $fakeUser,
                            'recipientName' => $toName,
                            'ticketUrl'     => url('tickets/1'),
                            'appName'       => env('APP_NAME', 'Support Portal'),
                        ])
                    );
                    break;

                case 'welcome':
                    $result = $emailService->notifyWelcome(
                        array_merge($fakeUser, ['email' => $toEmail, 'first_name' => $toName]),
                        'TempPass123!'
                    );
                    break;

                case 'password_reset':
                    $result = $emailService->sendPasswordReset(
                        $toEmail, $toName,
                        url('auth/reset-password/test-token-for-preview-only')
                    );
                    break;
            }

            $testResult = [
                'success' => $result,
                'message' => $result ? "✅ Email sent successfully to {$toEmail}!" : "❌ Failed to send. Check .env mail settings and logs.",
            ];
        }
    }
}

ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Email Notification Test</h1>
        <p class="page-subtitle">Send test emails to verify all notification templates.</p>
    </div>
    <span class="badge bg-warning text-dark">Dev Only</span>
</div>

<?php if ($testResult): ?>
<div class="alert alert-<?= $testResult['success'] ? 'success' : 'danger' ?> mb-4">
    <i class="bi bi-<?= $testResult['success'] ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
    <?= htmlspecialchars($testResult['message']) ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-envelope-fill me-2 text-primary"></i>Send Test Email</span>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('admin/email-test') ?>">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="mb-3">
                        <label class="form-label">Send To <span class="text-danger">*</span></label>
                        <input type="email" name="to_email" class="form-control"
                               value="<?= htmlspecialchars($_POST['to_email'] ?? '') ?>"
                               placeholder="test@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Recipient Name</label>
                        <input type="text" name="to_name" class="form-control"
                               value="<?= htmlspecialchars($_POST['to_name'] ?? 'Test User') ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Email Template <span class="text-danger">*</span></label>
                        <?php
                        $templates = [
                            'new_ticket'      => ['New Ticket Notification',    'bi-ticket-perforated-fill', 'primary'],
                            'ticket_assigned' => ['Ticket Assigned',            'bi-person-check-fill',      'success'],
                            'ticket_reply'    => ['New Reply Notification',     'bi-chat-dots-fill',         'purple'],
                            'ticket_resolved' => ['Ticket Resolved',            'bi-check-circle-fill',      'success'],
                            'status_changed'  => ['Status Changed',             'bi-arrow-repeat',           'warning'],
                            'welcome'         => ['Welcome Email',              'bi-person-plus-fill',       'info'],
                            'password_reset'  => ['Password Reset',             'bi-shield-lock-fill',       'danger'],
                        ];
                        foreach ($templates as $val => [$label, $icon, $color]):
                            $checked = ($_POST['test_type'] ?? 'new_ticket') === $val ? 'checked' : '';
                        ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio"
                                   name="test_type" id="type_<?= $val ?>"
                                   value="<?= $val ?>" <?= $checked ?>>
                            <label class="form-check-label" for="type_<?= $val ?>">
                                <i class="bi <?= $icon ?> text-<?= $color ?> me-2"></i>
                                <?= $label ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-send-fill me-2"></i>Send Test Email
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Mail Config Info -->
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header">
                <span><i class="bi bi-gear-fill me-2 text-primary"></i>Current Mail Configuration</span>
            </div>
            <div class="card-body p-0">
                <?php
                $config = [
                    ['Driver',       env('MAIL_DRIVER',      'Not set')],
                    ['Host',         env('MAIL_HOST',        'Not set')],
                    ['Port',         env('MAIL_PORT',        'Not set')],
                    ['Encryption',   env('MAIL_ENCRYPTION',  'Not set')],
                    ['Username',     env('MAIL_USERNAME') ? '••••••••' : 'Not set'],
                    ['From Address', env('MAIL_FROM_ADDRESS','Not set')],
                    ['From Name',    env('MAIL_FROM_NAME',   'Not set')],
                    ['PHPMailer',    class_exists(\PHPMailer\PHPMailer\PHPMailer::class) ? '✅ Installed' : '❌ Not installed (using mail())'],
                ];
                foreach ($config as [$label, $val]):
                ?>
                <div class="d-flex align-items-center px-3 py-2 border-bottom" style="font-size:.85rem;">
                    <span class="text-muted" style="width:140px;flex-shrink:0;"><?= $label ?></span>
                    <strong style="font-family:<?= str_contains($label,'Username')||str_contains($label,'Password') ? 'monospace' : 'inherit' ?>">
                        <?= htmlspecialchars((string)$val) ?>
                    </strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Mailtrap setup guide -->
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-info-circle-fill me-2 text-primary"></i>Setup Guide</span>
            </div>
            <div class="card-body" style="font-size:.875rem;">
                <p class="fw-semibold mb-2">For local development, use Mailtrap:</p>
                <ol style="padding-left:1.25rem;line-height:2;">
                    <li>Sign up free at <a href="https://mailtrap.io" target="_blank">mailtrap.io</a></li>
                    <li>Go to <strong>Inboxes → SMTP Settings</strong></li>
                    <li>Copy your credentials into <code>.env</code></li>
                </ol>
                <div style="background:#1e293b;color:#e2e8f0;border-radius:8px;padding:1rem;font-family:monospace;font-size:.8rem;line-height:1.8;">
                    MAIL_DRIVER=smtp<br>
                    MAIL_HOST=sandbox.smtp.mailtrap.io<br>
                    MAIL_PORT=2525<br>
                    MAIL_USERNAME=your_username<br>
                    MAIL_PASSWORD=your_password<br>
                    MAIL_ENCRYPTION=tls<br>
                    MAIL_FROM_ADDRESS=noreply@yourapp.com<br>
                    MAIL_FROM_NAME="Support Portal"
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size:.8rem;">
                    For production use Gmail, SendGrid, Mailgun, or any SMTP provider.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
