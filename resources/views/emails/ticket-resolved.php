<?php
/**
 * Ticket Resolved Email Template
 * Variables: $ticket, $resolvedBy, $recipientName, $ticketUrl, $appName
 */
$resolvedByName = trim(($resolvedBy['first_name'] ?? '') . ' ' . ($resolvedBy['last_name'] ?? ''));
?>

<p style="margin:0 0 20px;font-size:15px;">
    Hello <strong><?= htmlspecialchars($recipientName) ?></strong>,
</p>

<!-- Success banner -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);border:1px solid #86efac;border-radius:12px;margin:0 0 24px;">
    <tr>
        <td style="padding:20px 24px;text-align:center;">
            <p style="margin:0 0 6px;font-size:28px;">✅</p>
            <p style="margin:0;font-size:16px;font-weight:700;color:#166534;">Your Ticket Has Been Resolved!</p>
            <p style="margin:6px 0 0;font-size:13px;color:#166534;">
                Resolved by <strong><?= htmlspecialchars($resolvedByName) ?></strong>
                on <?= date('d M Y') ?>
            </p>
        </td>
    </tr>
</table>

<p style="margin:0 0 20px;font-size:15px;color:#374151;">
    We're happy to let you know that your support ticket has been marked as resolved.
    Please review the resolution and let us know if you need further assistance.
</p>

<!-- Ticket summary -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin:0 0 24px;">
    <tr>
        <td style="padding:20px 24px;">
            <p style="margin:0 0 6px;font-size:12px;font-family:monospace;color:#64748b;">
                <?= htmlspecialchars($ticket['ticket_number'] ?? '') ?>
            </p>
            <h2 style="margin:0 0 16px;font-size:16px;color:#0f172a;font-weight:700;">
                <?= htmlspecialchars($ticket['subject'] ?? '') ?>
            </h2>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="border-top:1px solid #e2e8f0;padding-top:12px;">
                <tr>
                    <td style="font-size:12px;color:#64748b;">
                        <strong style="color:#374151;">Status:</strong>
                        <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:20px;font-size:11px;">
                            Resolved
                        </span><br><br>
                        <strong style="color:#374151;">Resolved by:</strong> <?= htmlspecialchars($resolvedByName) ?><br>
                        <strong style="color:#374151;">Resolution date:</strong> <?= date('d M Y, H:i') ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Action buttons -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding-right:12px;">
                        <a href="<?= htmlspecialchars($ticketUrl) ?>"
                           style="display:inline-block;background:linear-gradient(135deg,#198754,#146c43);color:#ffffff;text-decoration:none;padding:13px 24px;border-radius:10px;font-weight:600;font-size:13px;">
                            ✓ View Resolution
                        </a>
                    </td>
                    <td>
                        <a href="<?= htmlspecialchars($ticketUrl) ?>"
                           style="display:inline-block;background:#ffffff;color:#0d6efd;text-decoration:none;padding:12px 24px;border-radius:10px;font-weight:600;font-size:13px;border:1.5px solid #0d6efd;">
                            ↩ Reopen if Needed
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Satisfaction note -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;margin:0 0 20px;">
    <tr>
        <td style="padding:14px 18px;font-size:13px;color:#92400e;">
            <strong>Was this resolved to your satisfaction?</strong><br>
            If the issue persists or you need more help, simply reply to this ticket
            or <a href="<?= htmlspecialchars(url('tickets/create')) ?>" style="color:#92400e;font-weight:600;">open a new ticket</a>.
        </td>
    </tr>
</table>
