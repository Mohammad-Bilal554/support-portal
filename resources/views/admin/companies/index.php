<?php
/**
 * Companies Index View
 */
use App\Models\Company;
$title = 'Company Management';
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Company Management</h1>
        <p class="page-subtitle">Manage client companies and their users.</p>
    </div>
    <a href="<?= url('admin/companies/create') ?>" class="btn btn-primary">
        <i class="bi bi-building-add me-1"></i> Add Company
    </a>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Total Companies', $summary['total'],    'bi-building-fill',  'bg-primary bg-opacity-10 text-primary'],
        ['Active',          $summary['active'],   'bi-check-circle-fill','bg-success bg-opacity-10 text-success'],
        ['Inactive',        $summary['inactive'], 'bi-x-circle-fill',  'bg-danger bg-opacity-10 text-danger'],
    ] as [$label, $val, $icon, $cls]): ?>
    <div class="col-sm-4">
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
<form method="GET" action="<?= url('admin/companies') ?>" class="filter-bar mb-3">
    <div class="search-box">
        <i class="bi bi-search search-icon"></i>
        <input type="text" name="search" class="form-control"
               placeholder="Search by name, email or website…"
               value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
    </div>

    <select name="is_active" class="form-select" style="width:auto;">
        <option value="">All Status</option>
        <option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Inactive</option>
    </select>

    <button type="submit" class="btn btn-primary btn-sm">
        <i class="bi bi-funnel-fill me-1"></i>Filter
    </button>
    <?php if (array_filter($filters)): ?>
    <a href="<?= url('admin/companies') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-x-lg me-1"></i>Clear
    </a>
    <?php endif; ?>
</form>

<!-- Companies Table -->
<div class="table-card">
    <div class="card-header">
        <span>
            <i class="bi bi-building-fill me-2 text-primary"></i>Companies
            <span class="badge bg-secondary ms-1" style="font-size:.7rem;">
                <?= number_format($companies['total']) ?>
            </span>
        </span>
        <input type="text" class="form-control form-control-sm"
               placeholder="Quick search…"
               data-table-search="companiesTable"
               style="width:180px;">
    </div>

    <?php if (empty($companies['data'])): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="bi bi-building"></i></div>
        <h6>No companies found</h6>
        <p>Add your first company to get started assigning clients.</p>
        <a href="<?= url('admin/companies/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-building-add me-1"></i>Add Company
        </a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table" id="companiesTable">
            <thead>
                <tr>
                    <th style="width:48px;">#</th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th class="text-center">Users</th>
                    <th class="text-center">Tickets</th>
                    <th class="text-center">Open</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="width:110px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companies['data'] as $c): ?>
                <tr>
                    <td class="text-muted" style="font-size:.8rem;"><?= $c['id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <!-- Logo -->
                            <img src="<?= htmlspecialchars(Company::logoUrl($c)) ?>"
                                 width="38" height="38"
                                 style="border-radius:8px;object-fit:cover;border:1.5px solid var(--border-color);flex-shrink:0;">
                            <div>
                                <div style="font-weight:600;font-size:.875rem;">
                                    <?= htmlspecialchars($c['name']) ?>
                                </div>
                                <?php if ($c['website']): ?>
                                <a href="<?= htmlspecialchars($c['website']) ?>"
                                   target="_blank"
                                   style="font-size:.75rem;color:var(--text-muted);">
                                    <?= htmlspecialchars(str_replace(['https://','http://'], '', $c['website'])) ?>
                                    <i class="bi bi-box-arrow-up-right" style="font-size:.65rem;"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:.825rem;"><?= htmlspecialchars($c['email']) ?></div>
                        <?php if ($c['phone']): ?>
                        <div style="font-size:.775rem;color:var(--text-muted);">
                            <?= htmlspecialchars($c['phone']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:.78rem;">
                            <?= (int)$c['user_count'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:.78rem;">
                            <?= (int)$c['ticket_count'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ((int)$c['open_tickets'] > 0): ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger" style="font-size:.78rem;">
                            <?= (int)$c['open_tickets'] ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($c['is_active']): ?>
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
                        <?= format_date($c['created_at']) ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= url('admin/companies/' . $c['id']) ?>"
                               class="btn btn-icon btn-sm btn-outline-primary"
                               title="Edit" data-bs-toggle="tooltip">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-icon btn-sm <?= $c['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                    title="<?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>"
                                    data-bs-toggle="tooltip"
                                    onclick="toggleCompany(<?= $c['id'] ?>, this)">
                                <i class="bi <?= $c['is_active'] ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' ?>"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-icon btn-sm btn-outline-danger"
                                    title="Delete" data-bs-toggle="tooltip"
                                    data-confirm="Delete '<?= htmlspecialchars($c['name']) ?>'? Companies with active users cannot be deleted."
                                    data-action="<?= url('admin/companies/' . $c['id']) ?>"
                                    data-method="DELETE">
                                <i class="bi bi-trash-fill"></i>
                            </button>
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
        $paginator = $companies;
        $baseUrl   = url('admin/companies');
        include base_path('resources/views/partials/pagination.php');
        ?>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleCompany(id, btn) {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch(`<?= url('admin/companies') ?>/${id}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrf,
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            SupportPortal.showToast(data.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            SupportPortal.showToast(data.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    })
    .catch(() => {
        SupportPortal.showToast('Something went wrong.', 'danger');
        btn.disabled = false;
        btn.innerHTML = orig;
    });
}
</script>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
