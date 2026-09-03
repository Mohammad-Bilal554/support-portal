<?php
/**
 * New Ticket Email Template
 * Variables: $ticket, $creator, $recipientName, $ticketUrl, $appName
 */
$priorityColors = ['low'=>'#198754','medium'=>'#fd7e14','high'=>'#dc3545','critical'=>'#1e293b'];
$statusBg       = '#dbeafe';
$priorityColor  = $priorityColors[$ticket['priority'] ?? 'medium'] ?? '#6c757d';
$priorityLabel  = ucfirst($ticket['priority'] ?? 'medium');
$creatorName    = trim(($creator['first_name'] ?? '') . ' ' . ($creator['last_name'] ?? ''));
?>

<p style="margin:0 0 20px;font-size:15px;">Hello <strong><?= htmlspecialchars($recipientName) ?></strong>,</p>

<p style="margin:0 0 20px;font-size:15px;">
    A new support ticket has been submitted and requires your attention.
</p>

<!-- Ticket Card -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin:0 0 24px;">
    <tr>
        <td style="padding:20px 24px;">
            <!-- Ticket number + badges -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
                <tr>
                    <td>
                        <span style="font-family:monospace;font-size:12px;background:#e2e8f0;color:#64748b;padding:3px 10px;border-radius:20px;">
                            <?= htmlspecialchars($ticket['ticket_number'] ?? '') ?>
                        </span>
                    </td>
                    <td align="right">
                        <span style="font-size:11px;background:<?= $priorityColor ?>22;color:<?= $priorityColor ?>;padding:3px 10px;border-radius:20px;border:1px solid <?= $priorityColor ?>44;">
                            <?= $priorityLabel ?> Priority
                        </span>
                    </td>
                </tr>
            </table>

            <!-- Subject -->
            <h2 style="margin:0 0 10px;font-size:17px;color:#0f172a;font-weight:700;line-height:1.4;">
                <?= htmlspecialchars($ticket['subject'] ?? '') ?>
            </h2>

            <!-- Description preview -->
            <p style="margin:0 0 16px;font-size:14px;color:#475569;line-height:1.6;">
                <?= htmlspecialchars(mb_substr($ticket['description'] ?? '', 0, 300)) ?>
                <?= strlen($ticket['description'] ?? '') > 300 ? '…' : '' ?>
            </p>

            <!-- Meta row -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="border-top:1px solid #e2e8f0;padding-top:14px;margin-top:4px;">
                <tr>
                    <td style="font-size:12px;color:#64748b;">
                        <strong style="color:#374151;">Submitted by:</strong> <?= htmlspecialchars($creatorName) ?><br>
                        <strong style="color:#374151;">Email:</strong> <?= htmlspecialchars($creator['email'] ?? '') ?><br>
                        <?php if (!empty($ticket['category_name'])): ?>
                        <strong style="color:#374151;">Category:</strong> <?= htmlspecialchars($ticket['category_name']) ?><br>
                        <?php endif; ?>
                        <strong style="color:#374151;">Date:</strong> <?= date('d M Y, H:i') ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- CTA Button -->
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
    <tr>
        <td>
            <a href="<?= htmlspecialchars($ticketUrl) ?>"
               class="btn-cta"
               style="display:inline-block;background:linear-gradient(135deg,#0d6efd,#0a58ca);color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:600;font-size:14px;letter-spacing:0.3px;">
                View &amp; Respond to Ticket →
            </a>
        </td>
    </tr>
</table>

<p style="margin:0;font-size:13px;color:#94a3b8;">
    If the button doesn't work, copy this link into your browser:<br>
    <a href="<?= htmlspecialchars($ticketUrl) ?>" style="color:#0d6efd;word-break:break-all;">
        <?= htmlspecialchars($ticketUrl) ?>
    </a>
</p>
