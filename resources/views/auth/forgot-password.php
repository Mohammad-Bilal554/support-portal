<?php
use App\Core\Session;
use App\Core\Csrf;
$session   = Session::getInstance();
$errors    = $session->getFlash('errors') ?? [];
$old       = $session->getFlash('old')    ?? [];
$csrfToken = Csrf::getToken();
ob_start();
?>
<h1 class="auth-title">Forgot password?</h1>
<p class="auth-subtitle">Enter your email and we'll send you a reset link.</p>

<form action="<?= url('auth/forgot-password') ?>" method="POST" novalidate>
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
    <button type="submit" class="btn btn-auth"><i class="bi bi-send me-2"></i>Send Reset Link</button>
</form>
<div class="text-center mt-4">
    <a href="<?= url('auth/login') ?>" class="auth-link"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a>
</div>
<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/auth.php');
echo ob_get_clean();
