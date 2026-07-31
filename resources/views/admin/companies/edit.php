<?php
use App\Core\Session;
use App\Core\Csrf;
use App\Models\Company;
use App\Models\User;

$session   = Session::getInstance();
$errors    = $session->getFlash('errors') ?? [];
$old       = $session->getFlash('old')    ?? [];
$csrfToken = Csrf::getToken();
$c         = $company;
$title     = 'Edit Company';
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Company</h1>
        <p class="page-subtitle"><?= htmlspecialchars($c['name']) ?></p>
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

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Total Users',    $c['user_count']          ?? 0, 'bi-people-fill',         'bg-primary bg-opacity-10 text-primary'],
        ['Total Tickets',  $c['ticket_count']         ?? 0, 'bi-ticket-perforated-fill','bg-secondary bg-opacity-10 text-secondary'],
        ['Open Tickets',   $c['open_tickets']         ?? 0, 'bi-folder2-open',         'bg-danger bg-opacity-10 text-danger'],
        ['Resolved',       $c['resolved_tickets']     ?? 0, 'bi-check-circle-fill',    'bg-success bg-opacity-10 text-success'],
    ] as [$label, $val, $icon, $cls]): ?>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= (int)$val ?></div>
                    <div class="stat-label"><?= $label ?></div>
                </div>
                <div class="stat-icon <?= $cls ?>"><i class="bi <?= $icon ?>"></i></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">

    <!-- Left: Main Form -->
    <div class="col-lg-8">

        <!-- Company Info Form -->
        <form action="<?= url('admin/companies/' . $c['id']) ?>"
              method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

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
                                   value="<?= htmlspecialchars($old['name'] ?? $c['name']) ?>"
                                   required autofocus>
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
                                       value="<?= htmlspecialchars($old['email'] ?? $c['email']) ?>"
                                       required>
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['email'][0]) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="phone"
                                       class="form-control"
                                       value="<?= htmlspecialchars($old['phone'] ?? $c['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Website</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                <input type="url" name="website"
                                       class="form-control"
                                       value="<?= htmlspecialchars($old['website'] ?? $c['website'] ?? '') ?>"
                                       placeholder="https://www.company.com">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($old['address'] ?? $c['address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       name="is_active" id="isActive"
                                       value="1" <?= $c['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="isActive">Active Company</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>Save Changes
                </button>
                <a href="<?= url('admin/companies') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>

        <!-- Company Users -->
        <div class="table-card mt-4">
            <div class="card-header">
                <span><i class="bi bi-people-fill me-2 text-primary"></i>Company Users</span>
                <span class="badge bg-primary bg-opacity-10 text-primary"><?= count($users) ?></span>
            </div>
            <?php if (empty($users)): ?>
            <div class="empty-state py-4">
                <div class="empty-state-icon" style="font-size:2rem;"><i class="bi bi-person-plus"></i></div>
                <h6>No users yet</h6>
                <p>Assign users to this company from User Management.</p>
                <a href="<?= url('admin/users/create') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-person-plus me-1"></i>Add User
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th class="text-center">Tickets</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= htmlspecialchars(User::avatarUrl($u)) ?>"
                                         width="32" height="32"
                                         style="border-radius:50%;object-fit:cover;border:1.5px solid var(--border-color);">
                                    <div>
                                        <div style="font-weight:600;font-size:.85rem;">
                                            <?= htmlspecialchars(User::fullName($u)) ?>
                                        </div>
                                        <div style="font-size:.76rem;color:var(--text-muted);">
                                            <?= htmlspecialchars($u['email']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td><?= role_badge($u['role']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                    <?= (int)$u['ticket_count'] ?>
                                </span>
                            </td>
                            <td style="font-size:.8rem;color:var(--text-muted);">
                                <?= $u['last_login'] ? time_ago($u['last_login']) : 'Never' ?>
                            </td>
                            <td>
                                <a href="<?= url('admin/users/' . $u['id']) ?>"
                                   class="btn btn-sm btn-outline-primary btn-icon"
                                   title="Edit User">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Tickets -->
        <?php if (!empty($tickets)): ?>
        <div class="table-card mt-4">
            <div class="card-header">
                <span><i class="bi bi-ticket-perforated me-2 text-primary"></i>Recent Tickets</span>
                <a href="<?= url('tickets?company_id=' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Created By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                        <tr style="cursor:pointer;" onclick="window.location='<?= url('tickets/' . $t['id']) ?>'">
                            <td>
                                <code style="font-size:.73rem;color:var(--text-muted);">
                                    <?= htmlspecialchars($t['ticket_number']) ?>
                                </code>
                            </td>
                            <td style="max-width:200px;" class="text-truncate">
                                <?= htmlspecialchars($t['subject']) ?>
                            </td>
                            <td><?= status_badge($t['status']) ?></td>
                            <td><?= priority_badge($t['priority']) ?></td>
                            <td style="font-size:.82rem;">
                                <?= htmlspecialchars(trim(($t['first_name']??'').' '.($t['last_name']??''))) ?>
                            </td>
                            <td style="font-size:.78rem;color:var(--text-muted);">
                                <?= time_ago($t['created_at']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Right: Logo + Danger Zone -->
    <div class="col-lg-4">

        <!-- Logo Upload -->
        <form action="<?= url('admin/companies/' . $c['id']) ?>"
              method="POST" enctype="multipart/form-data" class="card mb-4">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="card-header">
                <span><i class="bi bi-image me-2 text-primary"></i>Company Logo</span>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <img id="logoPreview"
                         src="<?= htmlspecialchars(Company::logoUrl($c)) ?>"
                         width="100" height="100"
                         style="border-radius:12px;object-fit:cover;border:2px solid var(--border-color);">
                </div>
                <label for="logoFile" class="btn btn-outline-primary btn-sm d-block mb-2">
                    <i class="bi bi-upload me-1"></i>Change Logo
                </label>
                <input type="file" name="logo" id="logoFile"
                       accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
                       class="d-none">
                <?php if (!empty($c['logo'])): ?>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox"
                           name="remove_logo" id="removeLogo" value="1">
                    <label class="form-check-label text-danger" for="removeLogo" style="font-size:.8rem;">
                        Remove current logo
                    </label>
                </div>
                <?php endif; ?>
                <div class="text-muted mt-2" style="font-size:.75rem;">JPG, PNG, SVG • Max 2MB</div>
                <button type="submit" class="btn btn-sm btn-outline-secondary mt-3 w-100">
                    <i class="bi bi-check-lg me-1"></i>Update Logo
                </button>
            </div>
        </form>

        <!-- Company Details Card -->
        <div class="card mb-4">
            <div class="card-header">
                <span><i class="bi bi-info-circle me-2 text-primary"></i>Company Details</span>
            </div>
            <div class="card-body p-0">
                <?php foreach ([
                    ['Created',     format_date($c['created_at']),          'bi-calendar'],
                    ['Last Updated',format_date($c['updated_at'] ?? $c['created_at']), 'bi-clock-history'],
                    ['Status',      $c['is_active'] ? 'Active' : 'Inactive', 'bi-circle-fill'],
                ] as [$label, $val, $icon]): ?>
                <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom" style="font-size:.82rem;">
                    <i class="bi <?= $icon ?> text-primary" style="width:16px;flex-shrink:0;"></i>
                    <span class="text-muted flex-fill"><?= $label ?></span>
                    <strong><?= htmlspecialchars((string)$val) ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card border-danger">
            <div class="card-header" style="background:#fef2f2;color:#991b1b;">
                <span><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone</span>
            </div>
            <div class="card-body">
                <p style="font-size:.82rem;color:var(--text-secondary);margin-bottom:1rem;">
                    Deactivating a company prevents its users from creating tickets. All data is preserved.
                    Companies with active users cannot be deleted.
                </p>
                <button type="button"
                        class="btn btn-outline-danger btn-sm w-100"
                        data-confirm="Deactivate '<?= htmlspecialchars($c['name']) ?>'? Users will lose access."
                        data-action="<?= url('admin/companies/' . $c['id']) ?>"
                        data-method="DELETE">
                    <i class="bi bi-building-x me-1"></i>Deactivate Company
                </button>
            </div>
        </div>

    </div>
</div>

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
