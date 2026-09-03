<?php
/**
 * Ticket Assigned Email Template
 * Variables: $ticket, $assignee, $assignedBy, $recipientName, $ticketUrl, $appName
 */
$assignedByName = trim(($assignedBy['first_name'] ?? '') . ' ' . ($assignedBy['last_name'] ?? ''));
$priorityColors = ['low'=>'#198754','medium'=>'#fd7e14','high'=>'#dc3545','critical'=>'#1e293b'];
$priorityColor  = $priorityColors[$ticket['priority'] ?? 'medium'] ?? '#6c757d';
?>

<p style="margin:0 0 20px;font-size:15px;">
    Hello <strong><?= htmlspecialchars($recipientName) ?></strong>,
</p>

<p style="margin:0 0 20px;font-size:15px;">
    A support ticket has been assigned to you by <strong><?= htmlspecialchars($assignedByName) ?></strong>.
    Please review and take action as soon as possible.
</p>

<!-- Assignment notice -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;margin:0 0 24px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="margin:0;font-size:14px;color:#1d4ed8;">
                <strong>📋 Ticket Assigned to You</strong><br>
                You are now responsible for resolving this ticket.
            </p>
        </td>
    </tr>
</table>

<!-- Ticket Card -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin:0 0 24px;">
    <tr>
        <td style="padding:20px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
                <tr>
                    <td>
                        <span style="font-family:monospace;font-size:12px;background:#e2e8f0;color:#64748b;padding:3px 10px;border-radius:20px;">
                            <?= htmlspecialchars($ticket['ticket_number'] ?? '') ?>
                        </span>
                    </td>
                    <td align="right">
                        <span style="font-size:11px;background:<?= $priorityColor ?>22;color:<?= $priorityColor ?>;padding:3px 10px;border-radius:20px;border:1px solid <?= $priorityColor ?>44;">
                            <?= ucfirst($ticket['priority'] ?? 'medium') ?> Priority
                        </span>
                    </td>
                </tr>
            </table>
            <h2 style="margin:0 0 10px;font-size:17px;color:#0f172a;font-weight:700;">
                <?= htmlspecialchars($ticket['subject'] ?? '') ?>
            </h2>
            <p style="margin:0 0 16px;font-size:14px;color:#475569;line-height:1.6;">
                <?= htmlspecialchars(mb_substr($ticket['description'] ?? '', 0, 250)) ?>
                <?= strlen($ticket['description'] ?? '') > 250 ? '…' : '' ?>
            </p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="border-top:1px solid #e2e8f0;padding-top:12px;">
                <tr>
                    <td style="font-size:12px;color:#64748b;">
                        <strong style="color:#374151;">Assigned by:</strong> <?= htmlspecialchars($assignedByName) ?><br>
                        <strong style="color:#374151;">Status:</strong> <?= ucwords(str_replace('_',' ',$ticket['status']??'assigned')) ?><br>
                        <?php if (!empty($ticket['due_date'])): ?>
                        <strong style="color:#dc3545;">Due Date:</strong> <?= date('d M Y', strtotime($ticket['due_date'])) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- CTA -->
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
    <tr>
        <td>
            <a href="<?= htmlspecialchars($ticketUrl) ?>"
               style="display:inline-block;background:linear-gradient(135deg,#0d6efd,#0a58ca);color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:600;font-size:14px;">
                Open Ticket &amp; Start Working →
            </a>
        </td>
    </tr>
</table>

<p style="margin:0;font-size:13px;color:#94a3b8;">
    <a href="<?= htmlspecialchars($ticketUrl) ?>" style="color:#0d6efd;word-break:break-all;">
        <?= htmlspecialchars($ticketUrl) ?>
    </a>
</p>
