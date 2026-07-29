<?php
/**
 * Users Index View
 */
$title = 'User Management';
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage all portal users, roles and access.</p>
    </div>
    <a href="<?= url('admin/users/create') ?>" class="btn btn-primary">
        <i class="bi bi-person-plus-fill me-1"></i> Add User
    </a>
</div>

<!-- Role Count Cards -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Super Admins', $counts['super_admin'] ?? 0, 'bi-shield-fill-check', 'bg-purple-subtle text-purple', '#6f42c1'],
        ['Employees',    $counts['employee']    ?? 0, 'bi-person-badge-fill', 'bg-primary bg-opacity-10 text-primary', '#0d6efd'],
        ['Clients',      $counts['client']      ?? 0, 'bi-people-fill',       'bg-success bg-opacity-10 text-success', '#198754'],
        ['Total Active', array_sum($counts),          'bi-person-check-fill', 'bg-warning bg-opacity-10 text-warning', '#ffc107'],
    ] as [$label, $val, $icon, $cls, $color]): ?>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= $val ?></div>
                    <div class="stat-label"><?= $label ?></div>
                </div>
                <div class="stat-icon <?= $cls ?>">
                    <i class="bi <?= $icon ?>"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<form method="GET" action="<?= url('admin/users') ?>" class="filter-bar mb-3">
    <div class="search-box">
        <i class="bi bi-search search-icon"></i>
        <input type="text" name="search" class="form-control"
               placeholder="Search name or email…"
               value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
    </div>

    <select name="role" class="form-select" style="width:auto;">
        <option value="">All Roles</option>
        <option value="super_admin" <?= ($filters['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
        <option value="employee"    <?= ($filters['role'] ?? '') === 'employee'    ? 'selected' : '' ?>>Employee</option>
        <option value="client"      <?= ($filters['role'] ?? '') === 'client'      ? 'selected' : '' ?>>Client</option>
    </select>

    <select name="company_id" class="form-select" style="width:auto;">
        <option value="">All Companies</option>
        <?php foreach ($companies as $c): ?>
        <option value="<?= $c['id'] ?>" <?= ($filters['company_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>

    <select name="is_active" class="form-select" style="width:auto;">
        <option value="">All Status</option>
        <option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Inactive</option>
    </select>

    <button type="submit" class="btn btn-primary btn-sm">
        <i class="bi bi-funnel-fill me-1"></i>Filter
    </button>
    <?php if (array_filter($filters)): ?>
    <a href="<?= url('admin/users') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-x-lg me-1"></i>Clear
    </a>
    <?php endif; ?>
</form>

<!-- Users Table -->
<div class="table-card">
    <div class="card-header">
        <span>
            <i class="bi bi-people-fill me-2 text-primary"></i>Users
            <span class="badge bg-secondary ms-1" style="font-size:.7rem;"><?= number_format($users['total']) ?></span>
        </span>
        <div class="d-flex gap-2 align-items-center">
            <input type="text" class="form-control form-control-sm"
                   placeholder="Quick search table…"
                   data-table-search="usersTable"
                   style="width:180px;">
        </div>
    </div>

    <?php if (empty($users['data'])): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="bi bi-people"></i></div>
        <h6>No users found</h6>
        <p>Try adjusting your filters or create a new user.</p>
        <a href="<?= url('admin/users/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i>Add First User
        </a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table" id="usersTable">
            <thead>
                <tr>
                    <th style="width:48px;">#</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Company</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Joined</th>
                    <th style="width:110px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users['data'] as $u): ?>
                <tr>
                    <td class="text-muted" style="font-size:.8rem;"><?= $u['id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= htmlspecialchars(\App\Models\User::avatarUrl($u)) ?>"
                                 width="36" height="36"
                                 style="border-radius:50%;object-fit:cover;border:2px solid var(--border-color);">
                            <div>
                                <div style="font-weight:600;font-size:.875rem;">
                                    <?= htmlspecialchars(\App\Models\User::fullName($u)) ?>
                                    <?php if ((int)$u['id'] === auth_id()): ?>
                                    <span class="badge bg-primary ms-1" style="font-size:.6rem;">You</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:.775rem;color:var(--text-muted);">
                                    <?= htmlspecialchars($u['email']) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td><?= role_badge($u['role']) ?></td>
                    <td style="font-size:.85rem;">
                        <?= $u['company_name'] ? htmlspecialchars($u['company_name']) : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td>
                        <?php if ($u['is_active']): ?>
                        <span class="badge" style="background:#dcfce7;color:#166534;font-size:.72rem;">
                            <i class="bi bi-circle-fill me-1" style="font-size:.45rem;"></i>Active
                        </span>
                        <?php else: ?>
                        <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:.72rem;">
                            <i class="bi bi-circle-fill me-1" style="font-size:.45rem;"></i>Inactive
                        </span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem;color:var(--text-muted);">
                        <?= $u['last_login'] ? time_ago($u['last_login']) : 'Never' ?>
                    </td>
                    <td style="font-size:.8rem;color:var(--text-muted);">
                        <?= format_date($u['created_at']) ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= url('admin/users/' . $u['id']) ?>"
                               class="btn btn-icon btn-sm btn-outline-primary"
                               title="Edit" data-bs-toggle="tooltip">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <?php if ((int)$u['id'] !== auth_id()): ?>
                            <button type="button"
                                    class="btn btn-icon btn-sm <?= $u['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                    title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>"
                                    data-bs-toggle="tooltip"
                                    onclick="toggleUserStatus(<?= $u['id'] ?>, this)">
                                <i class="bi <?= $u['is_active'] ? 'bi-person-x-fill' : 'bi-person-check-fill' ?>"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-icon btn-sm btn-outline-danger"
                                    title="Delete" data-bs-toggle="tooltip"
                                    data-confirm="Delete user '<?= htmlspecialchars(\App\Models\User::fullName($u)) ?>'? This will deactivate the account."
                                    data-action="<?= url('admin/users/' . $u['id']) ?>"
                                    data-method="DELETE">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="px-3 py-2 border-top">
        <?php
        $paginator = $users;
        $baseUrl   = url('admin/users');
        include base_path('resources/views/partials/pagination.php');
        ?>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleUserStatus(userId, btn) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch(`<?= url('admin/users') ?>/${userId}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken,
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            SupportPortal.showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 800);
        } else {
            SupportPortal.showToast(data.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(() => {
        SupportPortal.showToast('Something went wrong.', 'danger');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
</script>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
