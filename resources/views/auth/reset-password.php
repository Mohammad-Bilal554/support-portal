<?php
use App\Core\Session;
use App\Core\Csrf;
$session   = Session::getInstance();
$errors    = $session->getFlash('errors') ?? [];
$csrfToken = Csrf::getToken();
$token     = $token ?? '';
ob_start();
?>
<h1 class="auth-title">Set new password</h1>
<p class="auth-subtitle">Must be at least 8 characters.</p>

<form action="<?= url('auth/reset-password') ?>" method="POST" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="token"       value="<?= htmlspecialchars($token) ?>">

    <div class="mb-3">
        <label for="password" class="form-label">New Password</label>
        <div class="input-group">
            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                   id="password" name="password" placeholder="Min. 8 characters" autocomplete="new-password" autofocus required>
            <button type="button" class="btn-password-toggle" tabindex="-1"><i class="bi bi-eye"></i></button>
        </div>
        <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password'][0]) ?></div>
        <?php endif; ?>
        <div class="mt-2">
            <div class="progress" style="height:4px;border-radius:4px;">
                <div id="strengthBar" class="progress-bar" style="width:0%;transition:all 0.3s;"></div>
            </div>
            <small id="strengthText" class="text-muted d-block mt-1" style="font-size:0.75rem;"></small>
        </div>
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <div class="input-group">
            <input type="password" class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                   id="password_confirmation" name="password_confirmation" placeholder="Repeat password" autocomplete="new-password" required>
            <button type="button" class="btn-password-toggle" tabindex="-1"><i class="bi bi-eye"></i></button>
        </div>
        <?php if (isset($errors['password_confirmation'])): ?>
            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password_confirmation'][0]) ?></div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-auth"><i class="bi bi-shield-lock me-2"></i>Reset Password</button>
</form>
<div class="text-center mt-4">
    <a href="<?= url('auth/login') ?>" class="auth-link"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a>
</div>
<script>
document.getElementById('password').addEventListener('input', function() {
    const v=this.value, bar=document.getElementById('strengthBar'), text=document.getElementById('strengthText');
    let s=0;
    if(v.length>=8) s++; if(/[A-Z]/.test(v)) s++; if(/[0-9]/.test(v)) s++; if(/[^A-Za-z0-9]/.test(v)) s++;
    const lvl=[{p:'0%',c:'',l:''},{p:'25%',c:'bg-danger',l:'Weak'},{p:'50%',c:'bg-warning',l:'Fair'},{p:'75%',c:'bg-info',l:'Good'},{p:'100%',c:'bg-success',l:'Strong'}];
    const l=lvl[s]||lvl[0]; bar.style.width=l.p; bar.className='progress-bar '+l.c; text.textContent=l.l;
});
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/auth.php');
