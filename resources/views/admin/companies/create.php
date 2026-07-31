<?php
use App\Core\Session;
use App\Core\Csrf;

$session   = Session::getInstance();
$errors    = $session->getFlash('errors') ?? [];
$old       = $session->getFlash('old')    ?? [];
$csrfToken = Csrf::getToken();
$title     = 'Add Company';
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Add Company</h1>
        <p class="page-subtitle">Register a new client company.</p>
    </div>
    <a href="<?= url('admin/companies') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

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

<form action="<?= url('admin/companies') ?>" method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="row g-4">

        <!-- Left: Form Fields -->
        <div class="col-lg-8">

            <!-- Company Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <span><i class="bi bi-building-fill me-2 text-primary"></i>Company Information</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                   class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                                   placeholder="Acme Corporation" autofocus required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['name'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email"
                                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                       value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                                       placeholder="contact@company.com" required>
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['email'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="phone"
                                       class="form-control"
                                       value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                                       placeholder="+1 555 0100">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Website</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                <input type="url" name="website"
                                       class="form-control <?= isset($errors['website']) ? 'is-invalid' : '' ?>"
                                       value="<?= htmlspecialchars($old['website'] ?? '') ?>"
                                       placeholder="https://www.company.com">
                            </div>
                            <?php if (isset($errors['website'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['website'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <textarea name="address"
                                          class="form-control"
                                          rows="2"
                                          placeholder="123 Business Ave, New York, USA 10001"><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       name="is_active" id="isActive"
                                       value="1" checked>
                                <label class="form-check-label fw-semibold" for="isActive">Active Company</label>
                                <div class="text-muted" style="font-size:.775rem;">
                                    Inactive companies cannot create tickets.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right: Logo -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <span><i class="bi bi-image me-2 text-primary"></i>Company Logo</span>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3" id="logoPreviewWrap">
                        <img id="logoPreview"
                             src="https://ui-avatars.com/api/?name=Company&background=e2e8f0&color=64748b&size=128&bold=true&length=2"
                             width="100" height="100"
                             style="border-radius:12px;object-fit:cover;border:2px solid var(--border-color);">
                    </div>
                    <label for="logoFile" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-upload me-1"></i>Upload Logo
                    </label>
                    <input type="file" name="logo" id="logoFile"
                           accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
                           class="d-none">
                    <div class="text-muted mt-2" style="font-size:.75rem;">
                        JPG, PNG, SVG, WEBP • Max 2MB
                    </div>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-lightbulb me-2 text-warning"></i>Tips</span>
                </div>
                <div class="card-body p-0">
                    <?php foreach ([
                        'Use a square logo for best display results.',
                        'Company email is used for billing notifications.',
                        'You can assign users to this company after creation.',
                        'Inactive companies are hidden from client portals.',
                    ] as $tip): ?>
                    <div class="d-flex align-items-start gap-2 px-3 py-2 border-bottom" style="font-size:.8rem;">
                        <i class="bi bi-check2 text-success mt-1" style="flex-shrink:0;"></i>
                        <span style="color:var(--text-secondary);"><?= $tip ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3 mt-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-building-add me-1"></i>Create Company
        </button>
        <a href="<?= url('admin/companies') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<script>
document.getElementById('logoFile').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        SupportPortal.showToast('File too large. Max 2MB.', 'danger');
        this.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => document.getElementById('logoPreview').src = e.target.result;
    reader.readAsDataURL(file);
});
</script>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
