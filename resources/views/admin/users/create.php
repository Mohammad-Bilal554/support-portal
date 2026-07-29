<?php
/**
 * Create User View
 */
use App\Core\Session;
use App\Core\Csrf;

$session   = Session::getInstance();
$errors    = $session->getFlash('errors') ?? [];
$old       = $session->getFlash('old')    ?? [];
$csrfToken = Csrf::getToken();
$title     = 'Create User';
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Create User</h1>
        <p class="page-subtitle">Add a new user to the support portal.</p>
    </div>
    <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Users
    </a>
</div>

<!-- Validation errors -->
<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <div>
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-1 ps-3">
            <?php foreach ($errors as $field => $msgs): ?>
                <?php foreach ((array)$msgs as $msg): ?>
                    <li style="font-size:.875rem;"><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<form action="<?= url('admin/users') ?>" method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="row g-4">
        <!-- Left column: form fields -->
        <div class="col-lg-8">

            <!-- Personal Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <span><i class="bi bi-person-fill me-2 text-primary"></i>Personal Information</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name"
                                   class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['first_name'] ?? '') ?>"
                                   placeholder="John" autofocus required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['first_name'][0]) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name"
                                   class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['last_name'] ?? '') ?>"
                                   placeholder="Smith" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['last_name'][0]) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email"
                                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                       value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                                       placeholder="john@example.com" required>
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['email'][0]) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="phone"
                                       class="form-control"
                                       value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                                       placeholder="+1 555 0100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <span><i class="bi bi-shield-lock-fill me-2 text-primary"></i>Account Settings</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role"
                                    class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>"
                                    id="roleSelect" required>
                                <option value="">Select Role…</option>
                                <option value="super_admin" <?= ($old['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                <option value="employee"    <?= ($old['role'] ?? '') === 'employee'    ? 'selected' : '' ?>>Employee</option>
                                <option value="client"      <?= ($old['role'] ?? '') === 'client'      ? 'selected' : '' ?>>Client</option>
                            </select>
                            <?php if (isset($errors['role'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['role'][0]) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6" id="companyField">
                            <label class="form-label">Company</label>
                            <select name="company_id" class="form-select">
                                <option value="">No Company</option>
                                <?php foreach ($companies as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($old['company_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="password"
                                       class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                       placeholder="Min. 8 characters" required>
                                <button type="button" class="input-group-text btn-password-toggle" style="cursor:pointer;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password'][0]) ?></div>
                            <?php endif; ?>
                            <!-- Strength bar -->
                            <div class="mt-2">
                                <div class="progress" style="height:3px;border-radius:3px;">
                                    <div id="pwStrengthBar" class="progress-bar" style="width:0%;transition:all .3s;"></div>
                                </div>
                                <small id="pwStrengthText" class="text-muted" style="font-size:.73rem;"></small>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox"
                                       name="is_active" id="isActive"
                                       value="1" <?= ($old['is_active'] ?? '1') !== '0' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isActive" style="font-weight:500;">
                                    Active Account
                                </label>
                                <div class="text-muted" style="font-size:.775rem;">User can log in when active.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right column: avatar + tip -->
        <div class="col-lg-4">

            <!-- Avatar Upload -->
            <div class="card mb-4">
                <div class="card-header">
                    <span><i class="bi bi-image me-2 text-primary"></i>Profile Photo</span>
                </div>
                <div class="card-body text-center">
                    <div id="avatarPreviewWrap" class="mb-3">
                        <img id="avatarPreview"
                             src="https://ui-avatars.com/api/?name=New+User&background=0d6efd&color=fff&size=128&bold=true"
                             width="100" height="100"
                             style="border-radius:50%;object-fit:cover;border:3px solid var(--border-color);">
                    </div>
                    <label for="avatarFile" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-upload me-1"></i>Upload Photo
                    </label>
                    <input type="file" name="avatar" id="avatarFile"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           class="d-none">
                    <div class="text-muted mt-2" style="font-size:.75rem;">
                        JPG, PNG, WEBP • Max 2MB
                    </div>
                </div>
            </div>

            <!-- Role Info Card -->
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-info-circle me-2 text-primary"></i>Role Permissions</span>
                </div>
                <div class="card-body p-0" id="roleInfoBody">
                    <div class="p-3 text-muted" style="font-size:.825rem;">
                        Select a role above to see permissions.
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Submit -->
    <div class="d-flex gap-3 mt-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i>Create User
        </button>
        <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

<script>
// Avatar preview
document.getElementById('avatarFile').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        SupportPortal.showToast('File is too large. Max 2MB.', 'danger');
        this.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
    reader.readAsDataURL(file);
});

// Password toggle
document.querySelector('.btn-password-toggle')?.addEventListener('click', function() {
    const input = document.getElementById('password');
    const icon  = this.querySelector('i');
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});

// Password strength
document.getElementById('password').addEventListener('input', function() {
    const v = this.value;
    let s = 0;
    if (v.length >= 8)             s++;
    if (/[A-Z]/.test(v))           s++;
    if (/[0-9]/.test(v))           s++;
    if (/[^A-Za-z0-9]/.test(v))    s++;
    const lvl = [
        {p:'0%',  c:'',           l:''},
        {p:'25%', c:'bg-danger',  l:'Weak'},
        {p:'50%', c:'bg-warning', l:'Fair'},
        {p:'75%', c:'bg-info',    l:'Good'},
        {p:'100%',c:'bg-success', l:'Strong'},
    ];
    const l = lvl[s] || lvl[0];
    const bar  = document.getElementById('pwStrengthBar');
    const text = document.getElementById('pwStrengthText');
    bar.style.width  = l.p;
    bar.className    = 'progress-bar ' + l.c;
    text.textContent = l.l;
});

// Role info
const roleInfo = {
    super_admin: [
        'Full access to all modules',
        'Manage users, companies, settings',
        'View all tickets & assign to employees',
        'Export reports (PDF & Excel)',
        'View activity logs',
        'Access REST API',
    ],
    employee: [
        'View & manage assigned tickets',
        'Change ticket status & add notes',
        'View reports dashboard',
        'Cannot access admin settings',
    ],
    client: [
        'Create and view own tickets',
        'Reply to own ticket conversations',
        'Cannot see other users\' tickets',
        'Cannot access admin panel',
    ],
};

document.getElementById('roleSelect').addEventListener('change', function() {
    const role  = this.value;
    const body  = document.getElementById('roleInfoBody');
    const comp  = document.getElementById('companyField');

    // Show/hide company field
    comp.style.opacity = (role === 'client') ? '1' : '0.5';

    if (!role || !roleInfo[role]) {
        body.innerHTML = '<div class="p-3 text-muted" style="font-size:.825rem;">Select a role to see permissions.</div>';
        return;
    }

    const items = roleInfo[role].map(i =>
        `<div class="d-flex align-items-start gap-2 px-3 py-2 border-bottom" style="font-size:.8rem;">
            <i class="bi bi-check-circle-fill text-success mt-1" style="font-size:.7rem;flex-shrink:0;"></i>
            <span>${i}</span>
        </div>`
    ).join('');
    body.innerHTML = items;
});
</script>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
