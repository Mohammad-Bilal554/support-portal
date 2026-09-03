<?php
/**
 * Welcome Email Template
 * Variables: $user, $recipientName, $temporaryPassword, $loginUrl, $appName, $role
 */
?>

<p style="margin:0 0 20px;font-size:15px;">
    Hello <strong><?= htmlspecialchars($recipientName) ?></strong>, welcome aboard! 🎉
</p>

<p style="margin:0 0 20px;font-size:15px;color:#374151;">
    Your account has been created on <strong><?= htmlspecialchars($appName) ?></strong>.
    You can now log in and start managing your support tickets.
</p>

<!-- Account details card -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin:0 0 24px;">
    <tr>
        <td style="padding:24px;">
            <p style="margin:0 0 16px;font-size:13px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:0.5px;">
                Your Account Details
            </p>

            <?php foreach ([
                ['Email',    $user['email']  ?? ''],
                ['Role',     $role           ?? 'Client'],
                ['Name',     $recipientName  ?? ''],
            ] as [$label, $val]): ?>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;">
                <tr>
                    <td width="120" style="font-size:13px;color:#64748b;vertical-align:top;padding-top:2px;">
                        <?= $label ?>:
                    </td>
                    <td style="font-size:13px;color:#0f172a;font-weight:600;">
                        <?= htmlspecialchars($val) ?>
                    </td>
                </tr>
            </table>
            <?php endforeach; ?>

            <?php if (!empty($temporaryPassword)): ?>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;margin-top:16px;">
                <tr>
                    <td style="padding:12px 16px;">
                        <p style="margin:0;font-size:13px;color:#92400e;">
                            <strong>Temporary Password:</strong>
                            <span style="font-family:monospace;background:#fef3c7;padding:2px 8px;border-radius:4px;margin-left:6px;">
                                <?= htmlspecialchars($temporaryPassword) ?>
                            </span>
                        </p>
                        <p style="margin:6px 0 0;font-size:12px;color:#92400e;">
                            ⚠️ Please change your password after first login.
                        </p>
                    </td>
                </tr>
            </table>
            <?php endif; ?>
        </td>
    </tr>
</table>

<!-- What you can do -->
<p style="margin:0 0 12px;font-size:14px;font-weight:700;color:#374151;">
    What you can do:
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
    <?php
    $features = match($role ?? 'Client') {
        'Super Admin' => [
            ['🎫', 'Create and manage all support tickets'],
            ['👥', 'Manage users, companies, and roles'],
            ['📊', 'View reports and activity logs'],
            ['⚙️', 'Configure portal settings'],
        ],
        'Employee', 'Support Agent' => [
            ['🎫', 'View and respond to assigned tickets'],
            ['🔄', 'Update ticket status and priority'],
            ['📝', 'Add internal notes for team communication'],
            ['📊', 'View your performance reports'],
        ],
        default => [
            ['🎫', 'Submit new support tickets'],
            ['💬', 'Reply to your ticket conversations'],
            ['📎', 'Attach files to your tickets'],
            ['🔔', 'Get email notifications on updates'],
        ],
    };
    foreach ($features as [$icon, $text]):
    ?>
    <tr>
        <td style="padding:5px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="28" style="font-size:16px;"><?= $icon ?></td>
                    <td style="font-size:14px;color:#374151;"><?= htmlspecialchars($text) ?></td>
                </tr>
            </table>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<!-- CTA -->
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
    <tr>
        <td>
            <a href="<?= htmlspecialchars($loginUrl) ?>"
               style="display:inline-block;background:linear-gradient(135deg,#0d6efd,#0a58ca);color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:600;font-size:15px;">
                Login to Your Account →
            </a>
        </td>
    </tr>
</table>

<p style="margin:0;font-size:13px;color:#94a3b8;">
    Need help? Contact us at
    <a href="mailto:<?= htmlspecialchars(env('MAIL_FROM_ADDRESS','support@portal.com')) ?>"
       style="color:#0d6efd;">
        <?= htmlspecialchars(env('MAIL_FROM_ADDRESS','support@portal.com')) ?>
    </a>
</p>
