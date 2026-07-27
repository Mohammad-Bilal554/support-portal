<?php $appName=$appName??'Support Portal'; $name=$name??'User'; $resetUrl=$resetUrl??'#'; $expires=$expiresIn??'1 hour'; ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Reset Password</title>
<style>body{margin:0;padding:20px;font-family:'Segoe UI',Arial,sans-serif;background:#f1f5f9;}.wrap{max-width:600px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}.hd{background:linear-gradient(135deg,#0d6efd,#0a58ca);padding:28px 32px;text-align:center;color:#fff;}.hd h1{margin:0;font-size:1.3rem;font-weight:600;}.bd{padding:32px 36px;color:#374151;font-size:.95rem;line-height:1.7;}.bd h2{color:#0f172a;font-size:1.1rem;margin-top:0;}.btn{display:inline-block;background:linear-gradient(135deg,#0d6efd,#0a58ca);color:#fff!important;text-decoration:none;padding:13px 28px;border-radius:10px;font-weight:600;margin:18px 0;}.url{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;word-break:break-all;font-size:.8rem;color:#64748b;margin-top:8px;}.warn{background:#fffbeb;border-left:4px solid #f59e0b;padding:12px 14px;border-radius:0 8px 8px 0;font-size:.85rem;color:#92400e;margin:18px 0;}.ft{background:#f8fafc;padding:18px 36px;text-align:center;color:#94a3b8;font-size:.78rem;border-top:1px solid #e2e8f0;}</style>
</head><body><div class="wrap">
<div class="hd"><h1>🔐 <?=htmlspecialchars($appName)?></h1><p style="margin:4px 0 0;opacity:.8;font-size:.85rem;">Password Reset Request</p></div>
<div class="bd"><h2>Hi <?=htmlspecialchars($name)?>!</h2>
<p>We received a request to reset your password. Click the button below:</p>
<div style="text-align:center;"><a href="<?=htmlspecialchars($resetUrl)?>" class="btn">Reset My Password</a></div>
<p style="font-size:.85rem;color:#64748b;">Or copy this link:</p><div class="url"><?=htmlspecialchars($resetUrl)?></div>
<div class="warn">⏰ This link expires in <?=htmlspecialchars($expires)?>. If you didn't request this, ignore this email.</div>
<p style="font-size:.85rem;color:#94a3b8;">Never share this link. Our team will never ask for your password.</p>
</div>
<div class="ft"><p>&copy; <?=date('Y')?> <?=htmlspecialchars($appName)?>. All rights reserved.</p></div>
</div></body></html>
