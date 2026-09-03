<?php
/**
 * Activity Logs View
 */
use App\Models\User;

$title = 'Activity Logs';
ob_start();

// Action badge colors
$actionColors = [
    'ticket_created'   => ['bg' => '#dbeafe', 'color' => '#1d4ed8'],
    'ticket_updated'   => ['bg' => '#fffbeb', 'color' => '#92400e'],
    'ticket_deleted'   => ['bg' => '#fee2e2', 'color' => '#991b1b'],
    'status_changed'   => ['bg' => '#f3e8ff', 'color' => '#6d28d9'],
    'ticket_assigned'  => ['bg' => '#ecfdf5', 'color' => '#065f46'],
    'reply_added'      => ['bg' => '#f0fdf4', 'color' => '#166534'],
    'user_created'     => ['bg' => '#dbeafe', 'color' => '#1e40af'],
    'user_updated'     => ['bg' => '#fffbeb', 'color' => '#92400e'],
    'user_deleted'     => ['bg' => '#fee2e2', 'color' => '#991b1b'],
    'company_created'  => ['bg' => '#dbeafe', 'color' => '#1e40af'],
    'company_updated'  => ['bg' => '#fffbeb', 'color' => '#92400e'],
    'company_deleted'  => ['bg' => '#fee2e2', 'color' => '#991b1b'],
    'login'            => ['bg' => '#f0fdf4', 'color' => '#166534'],
    'logout'           => ['bg' => '#f1f5f9', 'color' => '#475569'],
    'api_request'      => ['bg' => '#fdf4ff', 'color' => '#7e22ce'],
];

$actionIcons = [
    'ticket_created'   => 'bi-ticket-perforated-fill',
    'ticket_updated'   => 'bi-pencil-fill',
    'ticket_deleted'   => 'bi-trash-fill',
    'status_changed'   => 'bi-arrow-repeat',
    'ticket_assigned'  => 'bi-person-check-fill',
    'reply_added'      => 'bi-chat-dots-fill',
    'user_created'     => 'bi-person-plus-fill',
    'user_updated'     => 'bi-person-gear',
    'user_deleted'     => 'bi-person-x-fill',
    'company_created'  => 'bi-building-add',
    'company_updated'  => 'bi-building-gear',
    'company_deleted'  => 'bi-building-x',
    'login'            => 'bi-box-arrow-in-right',
    'logout'           => 'bi-box-arrow-right',
    'api_request'      => 'bi-code-slash',
];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Activity Logs</h1>
        <p class="page-subtitle">Complete audit trail of all portal actions.</p>
    </div>
    <a href="<?= url('admin/logs?' . http_build_query(array_merge($filters, ['export' => 'csv']))) ?>"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Today',      $summary['today'],      'bi-calendar-day',   'bg-primary bg-opacity-10 text-primary'],
        ['This Week',  $summary['this_week'],  'bi-calendar-week',  'bg-info bg-opacity-10 text-info'],
        ['This Month', $summary['this_month'], 'bi-calendar-month', 'bg-success bg-opacity-10 text-success'],
        ['Total',      $summary['total'],      'bi-journal-text',   'bg-secondary bg-opacity-10 text-secondary'],
    ] as [$label, $val, $icon, $cls]): ?>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= number_format($val) ?></div>
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
<form method="GET" action="<?= url('admin/logs') ?>" class="card mb-4">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <div class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search logs…"
                           value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
            </div>

            <div class="col-md-2">
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $a): ?>
                    <option value="<?= $a['action'] ?>"
                        <?= $filters['action'] === $a['action'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $a['action']))) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="entity_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <?php foreach ($entityTypes as $et): ?>
                    <option value="<?= $et['entity_type'] ?>"
                        <?= $filters['entity_type'] === $et['entity_type'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($et['entity_type'])) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>"
                        <?= $filters['user_id'] == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(trim($u['first_name'] . ' ' . $u['last_name'])) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-1">
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['date_from']) ?>"
                       title="From date">
            </div>

            <div class="col-md-1">
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['date_to']) ?>"
                       title="To date">
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                <?php if (array_filter($filters)): ?>
                <a href="<?= url('admin/logs') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<!-- Logs Table -->
<div class="table-card">
    <div class="card-header">
        <span>
            <i class="bi bi-journal-text me-2 text-primary"></i>Log Entries
            <span class="badge bg-secondary ms-1" style="font-size:.7rem;">
                <?= number_format($logs['total']) ?>
            </span>
        </span>
        <span class="text-muted" style="font-size:.8rem;">
            Showing <?= $logs['from'] ?>–<?= $logs['to'] ?> of <?= number_format($logs['total']) ?>
        </span>
    </div>

    <?php if (empty($logs['data'])): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="bi bi-journal-x"></i></div>
        <h6>No log entries found</h6>
        <p>Try adjusting your filters to see results.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table" style="font-size:.83rem;">
            <thead>
                <tr>
                    <th style="width:48px;">#</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Entity</th>
                    <th>IP Address</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs['data'] as $log): ?>
                <?php
                    $style = $actionColors[$log['action']] ?? ['bg'=>'#f1f5f9','color'=>'#475569'];
                    $icon  = $actionIcons[$log['action']] ?? 'bi-activity';
                ?>
                <tr>
                    <td class="text-muted" style="font-size:.75rem;"><?= $log['id'] ?></td>

                    <td>
                        <?php if ($log['first_name']): ?>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= htmlspecialchars(User::avatarUrl([
                                'first_name' => $log['first_name'],
                                'last_name'  => $log['last_name'],
                                'avatar'     => $log['avatar'] ?? null,
                            ])) ?>"
                                 width="28" height="28"
                                 style="border-radius:50%;object-fit:cover;flex-shrink:0;">
                            <div>
                                <div style="font-weight:600;line-height:1.2;">
                                    <?= htmlspecialchars(trim($log['first_name'] . ' ' . $log['last_name'])) ?>
                                </div>
                                <div style="font-size:.72rem;color:var(--text-muted);">
                                    <?= role_badge($log['role'] ?? 'client') ?>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <span class="text-muted">System</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;background:<?= $style['bg'] ?>;color:<?= $style['color'] ?>;padding:.25rem .6rem;border-radius:20px;font-size:.72rem;font-weight:600;white-space:nowrap;">
                            <i class="bi <?= $icon ?>"></i>
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $log['action']))) ?>
                        </span>
                    </td>

                    <td style="max-width:260px;color:var(--text-secondary);">
                        <span class="text-truncate d-block">
                            <?= htmlspecialchars($log['description']) ?>
                        </span>
                    </td>

                    <td>
                        <?php if ($log['entity_type'] && $log['entity_id']): ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:.72rem;">
                            <?= htmlspecialchars(ucfirst($log['entity_type'])) ?>
                            #<?= $log['entity_id'] ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>

                    <td style="color:var(--text-muted);font-family:monospace;font-size:.78rem;">
                        <?= htmlspecialchars($log['ip_address'] ?? '—') ?>
                    </td>

                    <td style="white-space:nowrap;color:var(--text-muted);"
                        title="<?= htmlspecialchars($log['created_at']) ?>">
                        <?= time_ago($log['created_at']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="px-3 py-2 border-top">
        <?php
        $paginator = $logs;
        $baseUrl   = url('admin/logs');
        include base_path('resources/views/partials/pagination.php');
        ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
