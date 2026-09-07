<?php
/**
 * Ticket Reply Email Template
 * Variables: $ticket, $replier, $message, $recipientName, $ticketUrl, $appName
 */
$replierName = trim(($replier['first_name'] ?? '') . ' ' . ($replier['last_name'] ?? ''));
$replierRole = match($replier['role'] ?? 'client') {
    'super_admin' => 'Support Admin',
    'employee'    => 'Support Agent',
    default       => 'Client',
};
?>

<p style="margin:0 0 20px;font-size:15px;">
    Hello <strong><?= htmlspecialchars($recipientName) ?></strong>,
</p>

<p style="margin:0 0 20px;font-size:15px;">
    <strong><?= htmlspecialchars($replierName) ?></strong>
    (<?= $replierRole ?>) has replied to your support ticket.
</p>

<!-- Ticket reference -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#f8fafc;border-left:4px solid #0d6efd;border-radius:0 8px 8px 0;margin:0 0 20px;">
    <tr>
        <td style="padding:12px 16px;">
            <p style="margin:0;font-size:12px;color:#64748b;font-family:monospace;">
                <?= htmlspecialchars($ticket['ticket_number'] ?? '') ?>
            </p>
            <p style="margin:4px 0 0;font-size:14px;color:#0f172a;font-weight:600;">
                <?= htmlspecialchars($ticket['subject'] ?? '') ?>
            </p>
        </td>
    </tr>
</table>

<!-- Reply content -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin:0 0 24px;">
    <tr>
        <!-- Colour bar top -->
        <td colspan="2" style="background:#0d6efd;height:3px;padding:0;"></td>
    </tr>
    <tr>
        <td style="padding:20px 24px;">
            <!-- Author row -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
                <tr>
                    <td>
                        <p style="margin:0;font-size:13px;">
                            <strong style="color:#0f172a;"><?= htmlspecialchars($replierName) ?></strong>
                            <span style="color:#94a3b8;margin-left:6px;"><?= $replierRole ?></span>
                        </p>
                        <p style="margin:2px 0 0;font-size:12px;color:#94a3b8;">
                            <?= date('d M Y, H:i') ?>
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Message body -->
            <div style="font-size:14px;color:#374151;line-height:1.75;white-space:pre-wrap;">
<?= htmlspecialchars(mb_substr($message ?? '', 0, 600)) ?><?= strlen($message ?? '') > 600 ? "\n\n[Message truncated – click button below to read full reply]" : '' ?>
            </div>
        </td>
    </tr>
</table>

<!-- CTA -->
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
    <tr>
        <td>
            <a href="<?= htmlspecialchars($ticketUrl) ?>"
               style="display:inline-block;background:linear-gradient(135deg,#0d6efd,#0a58ca);color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:600;font-size:14px;">
                View Full Conversation →
            </a>
        </td>
    </tr>
</table>

<!-- Tip -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;margin:0 0 20px;">
    <tr>
        <td style="padding:12px 16px;font-size:13px;color:#166534;">
            💡 <strong>Tip:</strong> You can reply directly from the portal –
            <a href="<?= htmlspecialchars($ticketUrl) ?>" style="color:#166534;">click here to respond</a>.
        </td>
    </tr>
</table>

<p style="margin:0;font-size:13px;color:#94a3b8;">
    Ticket URL: <a href="<?= htmlspecialchars($ticketUrl) ?>" style="color:#0d6efd;word-break:break-all;">
        <?= htmlspecialchars($ticketUrl) ?>
    </a>
</p>
