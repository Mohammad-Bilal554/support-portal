<?php
$appName    = env('APP_NAME', 'Support Portal');
$appUrl     = env('APP_URL', '');
$pageTitle  = ($title ?? 'Auth') . ' — ' . $appName;
$session    = \App\Core\Session::getInstance();
$flashSuccess = $session->getFlash('success');
$flashError   = $session->getFlash('error');
$flashErrors  = $session->getFlash('errors') ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#0d6efd; --primary-dark:#0a58ca; }
        * { box-sizing:border-box; }
        body {
            font-family:'Inter',sans-serif;
            background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#0f172a 100%);
            min-height:100vh; display:flex; align-items:center;
            justify-content:center; padding:1rem; margin:0; overflow:hidden;
        }
        body::before, body::after {
            content:''; position:fixed; border-radius:50%;
            filter:blur(80px); opacity:0.12; pointer-events:none; z-index:0;
            animation:float 8s ease-in-out infinite alternate;
        }
        body::before { width:500px;height:500px;background:#3b82f6;top:-150px;left:-150px; }
        body::after  { width:400px;height:400px;background:#8b5cf6;bottom:-100px;right:-100px;animation-delay:-4s; }
        @keyframes float { 0%{transform:translate(0,0) scale(1)} 100%{transform:translate(30px,30px) scale(1.05)} }
        .auth-wrapper { position:relative;z-index:1;width:100%;max-width:440px; }
        .auth-card {
            background:rgba(255,255,255,0.98); border-radius:20px; padding:2.5rem;
            box-shadow:0 25px 60px rgba(0,0,0,0.35),0 0 0 1px rgba(255,255,255,0.05);
        }
        .auth-logo { display:flex;align-items:center;justify-content:center;gap:0.6rem;margin-bottom:1.75rem;text-decoration:none; }
        .auth-logo-icon { width:44px;height:44px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.25rem;box-shadow:0 4px 12px rgba(13,110,253,0.4); }
        .auth-logo-text { font-size:1.2rem;font-weight:700;color:#0f172a; }
        .auth-title    { font-size:1.45rem;font-weight:700;color:#0f172a;margin-bottom:0.3rem; }
        .auth-subtitle { color:#64748b;font-size:0.875rem;margin-bottom:1.75rem; }
        .form-label    { font-weight:500;font-size:0.85rem;color:#374151;margin-bottom:0.4rem; }
        .form-control  { border:1.5px solid #e2e8f0;border-radius:10px;padding:0.65rem 0.85rem;font-size:0.9rem;transition:all 0.2s;background:#f8fafc; }
        .form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px rgba(13,110,253,0.12);background:#fff; }
        .form-control.is-invalid { border-color:#dc3545; }
        .input-group .btn-password-toggle { border:1.5px solid #e2e8f0;border-left:none;border-radius:0 10px 10px 0;background:#f8fafc;color:#64748b;padding:0.65rem 0.85rem;cursor:pointer; }
        .input-group .form-control { border-radius:10px 0 0 10px; }
        .btn-auth { background:linear-gradient(135deg,var(--primary),var(--primary-dark));border:none;border-radius:10px;padding:0.75rem;font-size:0.9rem;font-weight:600;color:#fff;width:100%;transition:all 0.2s;box-shadow:0 4px 12px rgba(13,110,253,0.3); }
        .btn-auth:hover { transform:translateY(-1px);box-shadow:0 6px 20px rgba(13,110,253,0.4);color:#fff; }
        .btn-auth:disabled { opacity:0.7;cursor:not-allowed;transform:none; }
        .alert { border-radius:10px;font-size:0.875rem;border:none;padding:0.75rem 1rem; }
        .alert-danger  { background:#fef2f2;color:#991b1b; }
        .alert-success { background:#f0fdf4;color:#166534; }
        .auth-link { color:var(--primary);text-decoration:none;font-weight:500;font-size:0.875rem; }
        .auth-link:hover { text-decoration:underline; }
        .divider { display:flex;align-items:center;gap:0.75rem;margin:1.25rem 0;color:#94a3b8;font-size:0.8rem; }
        .divider::before,.divider::after { content:'';flex:1;height:1px;background:#e2e8f0; }
        .invalid-feedback { font-size:0.8rem; }
        .auth-footer { text-align:center;margin-top:1.5rem;color:#94a3b8;font-size:0.78rem; }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <?php if ($flashError): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?= htmlspecialchars($flashError) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($flashSuccess): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-check-circle-fill"></i>
        <span><?= htmlspecialchars($flashSuccess) ?></span>
    </div>
    <?php endif; ?>

    <div class="auth-card">
        <a href="<?= htmlspecialchars($appUrl) ?>" class="auth-logo">
            <div class="auth-logo-icon"><i class="bi bi-headset"></i></div>
            <span class="auth-logo-text"><?= htmlspecialchars($appName) ?></span>
        </a>
        <?= $content ?? '' ?>
    </div>
    <div class="auth-footer">&copy; <?= date('Y') ?> <?= htmlspecialchars($appName) ?>. All rights reserved.</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.btn-password-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = this.closest('.input-group').querySelector('input');
        const icon  = this.querySelector('i');
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
});
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const btn = this.querySelector('.btn-auth');
        if (btn) { btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Please wait...'; }
    });
});
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(el => {
        el.style.transition='opacity 0.5s'; el.style.opacity='0';
        setTimeout(()=>el.remove(),500);
    });
}, 6000);
</script>
</body>
</html>
