<?php
use App\Core\Session;
use App\Core\Csrf;
$session   = Session::getInstance();
$errors    = $session->getFlash('errors') ?? [];
$old       = $session->getFlash('old')    ?? [];
$csrfToken = Csrf::getToken();
ob_start();
?>
<h1 class="auth-title">Welcome back</h1>
<p class="auth-subtitle">Sign in to your account to continue</p>

<form action="<?= url('auth/login') ?>" method="POST" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
               id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>"
               placeholder="you@example.com" autocomplete="email" autofocus required>
        <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['email'][0]) ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-1">
        <div class="d-flex justify-content-between align-items-center">
            <label for="password" class="form-label mb-0">Password</label>
            <a href="<?= url('auth/forgot-password') ?>" class="auth-link" style="font-size:0.8rem;">Forgot password?</a>
        </div>
        <div class="input-group mt-1">
            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                   id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
            <button type="button" class="btn-password-toggle" tabindex="-1"><i class="bi bi-eye"></i></button>
        </div>
        <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password'][0]) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-check my-3">
        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
        <label class="form-check-label" for="remember" style="font-size:0.875rem;color:#64748b;">Keep me signed in for 30 days</label>
    </div>

    <button type="submit" class="btn btn-auth">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
    </button>
</form>

<?php if (env('APP_ENV') === 'local'): ?>
<div class="divider">demo credentials</div>
<div style="background:#f8fafc;border-radius:10px;padding:0.85rem 1rem;font-size:0.78rem;color:#64748b;line-height:2;">
    <div><i class="bi bi-shield-fill-check text-primary me-1"></i><strong>Admin:</strong> admin@support-portal.com / Admin@12345</div>
    <div><i class="bi bi-person-badge text-success me-1"></i><strong>Employee:</strong> john.smith@support.com / Employee@123</div>
    <div><i class="bi bi-person text-warning me-1"></i><strong>Client:</strong> mike.johnson@acme.com / Client@123</div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/auth.php');
