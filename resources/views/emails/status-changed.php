<?php
/**
 * Status Changed Email Template
 * Variables: $ticket, $oldStatus, $newStatus, $changedBy, $recipientName, $ticketUrl, $appName
 */
$changedByName = trim(($changedBy['first_name'] ?? '') . ' ' . ($changedBy['last_name'] ?? ''));

$statusColors = [
    'Open'               => ['bg'=>'#fee2e2','color'=>'#991b1b'],
    'Assigned'           => ['bg'=>'#fffbeb','color'=>'#92400e'],
    'In Progress'        => ['bg'=>'#dbeafe','color'=>'#1d4ed8'],
    'Waiting For Client' => ['bg'=>'#f0fdfa','color'=>'#065f46'],
    'Resolved'           => ['bg'=>'#dcfce7','color'=>'#166534'],
    'Closed'             => ['bg'=>'#f1f5f9','color'=>'#475569'],
];

$newStyle = $statusColors[$newStatus] ?? ['bg'=>'#f1f5f9','color'=>'#475569'];
?>

<p style="margin:0 0 20px;font-size:15px;">
    Hello <strong><?= htmlspecialchars($recipientName) ?></strong>,
</p>

<p style="margin:0 0 20px;font-size:15px;color:#374151;">
    The status of your support ticket has been updated
    by <strong><?= htmlspecialchars($changedByName) ?></strong>.
</p>

<!-- Status change visual -->
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
    <tr>
        <td>
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="background:#f1f5f9;color:#475569;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:600;text-align:center;">
                        <?= htmlspecialchars($oldStatus) ?>
                    </td>
                    <td style="padding:0 16px;color:#94a3b8;font-size:20px;">→</td>
                    <td style="background:<?= $newStyle['bg'] ?>;color:<?= $newStyle['color'] ?>;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:700;text-align:center;border:1.5px solid <?= $newStyle['color'] ?>44;">
                        <?= htmlspecialchars($newStatus) ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Ticket reference -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin:0 0 24px;">
    <tr>
        <td style="padding:20px 24px;">
            <p style="margin:0 0 4px;font-size:12px;font-family:monospace;color:#64748b;">
                <?= htmlspecialchars($ticket['ticket_number'] ?? '') ?>
            </p>
            <h2 style="margin:0 0 14px;font-size:16px;color:#0f172a;font-weight:700;">
                <?= htmlspecialchars($ticket['subject'] ?? '') ?>
            </h2>
            <p style="margin:0;font-size:12px;color:#64748b;">
                <strong style="color:#374151;">Updated by:</strong> <?= htmlspecialchars($changedByName) ?> &bull;
                <strong style="color:#374151;">Date:</strong> <?= date('d M Y, H:i') ?>
            </p>
        </td>
    </tr>
</table>

<!-- CTA -->
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 20px;">
    <tr>
        <td>
            <a href="<?= htmlspecialchars($ticketUrl) ?>"
               style="display:inline-block;background:linear-gradient(135deg,#0d6efd,#0a58ca);color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:600;font-size:14px;">
                View Ticket →
            </a>
        </td>
    </tr>
</table>
