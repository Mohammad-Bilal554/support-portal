<?php
/**
 * Password Reset Email Template
 * Variables: $name, $resetUrl, $expiresIn, $appName
 */
?>

<p style="margin:0 0 20px;font-size:15px;">
    Hello <strong><?= htmlspecialchars($name) ?></strong>,
</p>

<p style="margin:0 0 20px;font-size:15px;color:#374151;">
    We received a request to reset the password for your account.
    Click the button below to set a new password.
</p>

<!-- Security notice -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;margin:0 0 24px;">
    <tr>
        <td style="padding:14px 18px;font-size:13px;color:#1d4ed8;">
            🔒 <strong>This link will expire in <?= htmlspecialchars($expiresIn ?? '1 hour') ?>.</strong>
            If you did not request a password reset, you can safely ignore this email.
        </td>
    </tr>
</table>

<!-- CTA -->
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
    <tr>
        <td>
            <a href="<?= htmlspecialchars($resetUrl) ?>"
               style="display:inline-block;background:linear-gradient(135deg,#0d6efd,#0a58ca);color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:600;font-size:15px;">
                🔐 Reset My Password
            </a>
        </td>
    </tr>
</table>

<!-- URL fallback -->
<p style="margin:0 0 20px;font-size:13px;color:#64748b;">
    Or copy and paste this link into your browser:
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:0 0 24px;">
    <tr>
        <td style="padding:12px 16px;font-size:12px;word-break:break-all;">
            <a href="<?= htmlspecialchars($resetUrl) ?>" style="color:#0d6efd;">
                <?= htmlspecialchars($resetUrl) ?>
            </a>
        </td>
    </tr>
</table>

<!-- Warning -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;">
    <tr>
        <td style="padding:12px 16px;font-size:13px;color:#991b1b;">
            ⚠️ <strong>Security reminder:</strong> Our team will never ask for your password.
            If you didn't request this, please secure your account immediately.
        </td>
    </tr>
</table>
