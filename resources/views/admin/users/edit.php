<?php
/**
 * Edit User View
 */
use App\Core\Session;
use App\Core\Csrf;
use App\Models\User;

$session   = Session::getInstance();
$errors    = $session->getFlash('errors') ?? [];
$old       = $session->getFlash('old')    ?? [];
$csrfToken = Csrf::getToken();
$u         = $editUser; // the user being edited
$title     = 'Edit User';
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit User</h1>
        <p class="page-subtitle">Update account details for <?= htmlspecialchars(User::fullName($u)) ?>.</p>
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

<form action="<?= url('admin/users/' . $u['id']) ?>" method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="row g-4">

        <!-- Left: Form Fields -->
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
                                   value="<?= htmlspecialchars($old['first_name'] ?? $u['first_name']) ?>"
                                   required autofocus>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['first_name'][0]) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name"
                                   class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['last_name'] ?? $u['last_name']) ?>"
                                   required>
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
                                       value="<?= htmlspecialchars($old['email'] ?? $u['email']) ?>"
                                       required>
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
                                       value="<?= htmlspecialchars($old['phone'] ?? $u['phone'] ?? '') ?>">
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
                            <select name="role" class="form-select <?= isset($errors['role']) ? 'is-invalid' : '' ?>" required>
                                <option value="super_admin" <?= ($old['role'] ?? $u['role']) === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                <option value="employee"    <?= ($old['role'] ?? $u['role']) === 'employee'    ? 'selected' : '' ?>>Employee</option>
                                <option value="client"      <?= ($old['role'] ?? $u['role']) === 'client'      ? 'selected' : '' ?>>Client</option>
                            </select>
                            <?php if (isset($errors['role'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['role'][0]) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company</label>
                            <select name="company_id" class="form-select">
                                <option value="">No Company</option>
                                <?php foreach ($companies as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                    <?= ($old['company_id'] ?? $u['company_id']) == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- New password (optional) -->
                        <div class="col-12">
                            <label class="form-label">
                                New Password
                                <span class="text-muted" style="font-size:.75rem;font-weight:400;">(leave blank to keep current)</span>
                            </label>
                            <div class="input-group">
                                <input type="password" name="password" id="password"
                                       class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                       placeholder="Enter new password…"
                                       autocomplete="new-password">
                                <button type="button" class="input-group-text btn-password-toggle" style="cursor:pointer;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['password'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox"
                                       name="is_active" id="isActive" value="1"
                                       <?= ($old['is_active'] ?? $u['is_active']) ? 'checked' : '' ?>
                                       <?= ((int)$u['id'] === auth_id()) ? 'disabled' : '' ?>>
                                <label class="form-check-label fw-semibold" for="isActive">Active Account</label>
                                <?php if ((int)$u['id'] === auth_id()): ?>
                                    <div class="text-muted" style="font-size:.75rem;">Cannot deactivate your own account.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Account info -->
                        <div class="col-md-6">
                            <div style="background:#f8fafc;border-radius:8px;padding:.75rem;font-size:.8rem;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Last Login</span>
                                    <strong><?= $u['last_login'] ? time_ago($u['last_login']) : 'Never' ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Member Since</span>
                                    <strong><?= format_date($u['created_at']) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Email Verified</span>
                                    <?php if ($u['email_verified']): ?>
                                    <span class="badge" style="background:#dcfce7;color:#166534;font-size:.7rem;">Verified</span>
                                    <?php else: ?>
                                    <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:.7rem;">Unverified</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right: Avatar + Danger Zone -->
        <div class="col-lg-4">

            <!-- Avatar -->
            <div class="card mb-4">
                <div class="card-header">
                    <span><i class="bi bi-image me-2 text-primary"></i>Profile Photo</span>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img id="avatarPreview"
                             src="<?= htmlspecialchars(User::avatarUrl($u)) ?>"
                             width="100" height="100"
                             style="border-radius:50%;object-fit:cover;border:3px solid var(--border-color);">
                    </div>
                    <label for="avatarFile" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-upload me-1"></i>Change Photo
                    </label>
                    <input type="file" name="avatar" id="avatarFile"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           class="d-none">
                    <?php if (!empty($u['avatar'])): ?>
                    <div class="mt-2">
                        <div class="form-check d-inline-flex align-items-center gap-2">
                            <input class="form-check-input" type="checkbox"
                                   name="remove_avatar" id="removeAvatar" value="1">
                            <label class="form-check-label text-danger" for="removeAvatar" style="font-size:.8rem;">
                                Remove current photo
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="text-muted mt-2" style="font-size:.75rem;">JPG, PNG, WEBP • Max 2MB</div>
                </div>
            </div>

            <!-- Danger Zone -->
            <?php if ((int)$u['id'] !== auth_id()): ?>
            <div class="card border-danger" style="border-color:#dc3545 !important;">
                <div class="card-header" style="background:#fef2f2;color:#991b1b;">
                    <span><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone</span>
                </div>
                <div class="card-body">
                    <p style="font-size:.825rem;color:var(--text-secondary);margin-bottom:1rem;">
                        Deactivating this user will prevent them from logging in. All their tickets and data will be preserved.
                    </p>
                    <button type="button"
                            class="btn btn-outline-danger btn-sm w-100"
                            data-confirm="Deactivate user '<?= htmlspecialchars(User::fullName($u)) ?>'? They will lose access immediately."
                            data-action="<?= url('admin/users/' . $u['id']) ?>"
                            data-method="DELETE">
                        <i class="bi bi-person-x-fill me-1"></i>Deactivate User
                    </button>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Submit -->
    <div class="d-flex gap-3 mt-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Save Changes
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
        SupportPortal.showToast('File too large. Max 2MB.', 'danger');
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
</script>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
