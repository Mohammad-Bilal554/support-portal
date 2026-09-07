<?php
/**
 * Tickets List View
 */
$title = 'Tickets';
$user  = $authUser;
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <?= $user['role'] === 'client' ? 'My Tickets' : 'All Tickets' ?>
        </h1>
        <p class="page-subtitle">
            <?= number_format($tickets['total']) ?> ticket<?= $tickets['total'] != 1 ? 's' : '' ?> found
        </p>
    </div>
    <a href="<?= url('tickets/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>New Ticket
    </a>
</div>

<!-- Filters -->
<form method="GET" action="<?= url('tickets') ?>" class="filter-bar mb-3" id="filterForm">
    <div class="search-box">
        <i class="bi bi-search search-icon"></i>
        <input type="text" name="search" class="form-control"
               placeholder="Search tickets…"
               value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
    </div>

    <select name="status" class="form-select" style="width:auto;" onchange="this.form.submit()">
        <option value="">All Status</option>
        <?php foreach (\App\Models\Ticket::STATUSES as $val => $label): ?>
        <option value="<?= $val ?>" <?= ($filters['status'] ?? '') === $val ? 'selected' : '' ?>>
            <?= $label ?>
        </option>
        <?php endforeach; ?>
    </select>

    <select name="priority" class="form-select" style="width:auto;" onchange="this.form.submit()">
        <option value="">All Priority</option>
        <?php foreach (\App\Models\Ticket::PRIORITIES as $val => $label): ?>
        <option value="<?= $val ?>" <?= ($filters['priority'] ?? '') === $val ? 'selected' : '' ?>>
            <?= $label ?>
        </option>
        <?php endforeach; ?>
    </select>

    <select name="category_id" class="form-select" style="width:auto;" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['name']) ?>
        </option>
        <?php endforeach; ?>
    </select>

    <?php if (in_array($user['role'], ['super_admin', 'employee'])): ?>
    <select name="assigned_to" class="form-select" style="width:auto;" onchange="this.form.submit()">
        <option value="">All Assignees</option>
        <option value="<?= $user['id'] ?>" <?= ($filters['assigned_to'] ?? '') == $user['id'] ? 'selected' : '' ?>>
            My Tickets
        </option>
        <?php foreach ($employees as $emp): ?>
        <option value="<?= $emp['id'] ?>" <?= ($filters['assigned_to'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars(\App\Models\User::fullName($emp)) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary btn-sm">
        <i class="bi bi-funnel-fill me-1"></i>Filter
    </button>
    <?php if (array_filter($filters)): ?>
    <a href="<?= url('tickets') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-x-lg me-1"></i>Clear
    </a>
    <?php endif; ?>
</form>

<!-- Tickets Table -->
<div class="table-card">
    <div class="card-header">
        <span>
            <i class="bi bi-ticket-perforated-fill me-2 text-primary"></i>Tickets
        </span>
        <!-- Quick status tabs -->
        <div class="d-flex gap-1 flex-wrap">
            <?php
            $statusColors = [
                '' => 'secondary', 'open' => 'danger', 'assigned' => 'warning',
                'in_progress' => 'primary', 'resolved' => 'success', 'closed' => 'secondary'
            ];
            foreach (['' => 'All', 'open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved'] as $val => $label):
                $active = ($filters['status'] ?? '') === $val ? 'active' : '';
                $color  = $statusColors[$val] ?? 'secondary';
            ?>
            <a href="?<?= http_build_query(array_merge($filters, ['status' => $val, 'page' => 1])) ?>"
               class="btn btn-sm btn-<?= $active ? $color : 'outline-' . $color ?>"
               style="font-size:.75rem;padding:.25rem .6rem;">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($tickets['data'])): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="bi bi-ticket-perforated"></i></div>
        <h6>No tickets found</h6>
        <p><?= array_filter($filters) ? 'Try adjusting your filters.' : 'Create your first ticket to get started.' ?></p>
        <a href="<?= url('tickets/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>New Ticket
        </a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:130px;">#Ticket</th>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <?php if (in_array($user['role'], ['super_admin','employee'])): ?>
                    <th>Client</th>
                    <th>Assigned</th>
                    <?php endif; ?>
                    <th>Updated</th>
                    <th style="width:80px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets['data'] as $t): ?>
                <tr style="cursor:pointer;" onclick="window.location='<?= url('tickets/' . $t['id']) ?>'">
                    <td>
                        <code style="font-size:.75rem;color:var(--text-muted);">
                            <?= htmlspecialchars($t['ticket_number']) ?>
                        </code>
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:.875rem;max-width:260px;" class="text-truncate">
                            <?= htmlspecialchars($t['subject']) ?>
                        </div>
                        <?php if (!empty($t['company_name'])): ?>
                        <div style="font-size:.75rem;color:var(--text-muted);">
                            <i class="bi bi-building me-1"></i><?= htmlspecialchars($t['company_name']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($t['category_name']): ?>
                        <span class="badge rounded-pill"
                              style="background:<?= htmlspecialchars($t['category_color'] ?? '#6c757d') ?>22;
                                     color:<?= htmlspecialchars($t['category_color'] ?? '#6c757d') ?>;
                                     font-size:.72rem;border:1px solid <?= htmlspecialchars($t['category_color'] ?? '#6c757d') ?>44;">
                            <?= htmlspecialchars($t['category_name']) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:.8rem;">–</span>
                        <?php endif; ?>
                    </td>
                    <td><?= status_badge($t['status']) ?></td>
                    <td><?= priority_badge($t['priority']) ?></td>
                    <?php if (in_array($user['role'], ['super_admin','employee'])): ?>
                    <td style="font-size:.82rem;">
                        <?= htmlspecialchars(trim(($t['creator_first']??'').' '.($t['creator_last']??''))) ?: '–' ?>
                    </td>
                    <td style="font-size:.82rem;">
                        <?php if ($t['assignee_first']): ?>
                        <span style="color:var(--text-secondary);">
                            <?= htmlspecialchars(trim($t['assignee_first'].' '.$t['assignee_last'])) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">Unassigned</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td style="font-size:.78rem;color:var(--text-muted);white-space:nowrap;">
                        <?= time_ago($t['updated_at'] ?? $t['created_at']) ?>
                    </td>
                    <td onclick="event.stopPropagation()">
                        <div class="d-flex gap-1">
                            <a href="<?= url('tickets/' . $t['id']) ?>"
                               class="btn btn-icon btn-sm btn-outline-primary"
                               title="View">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                            <?php if (in_array($user['role'], ['super_admin','employee'])): ?>
                            <a href="<?= url('tickets/' . $t['id'] . '/edit') ?>"
                               class="btn btn-icon btn-sm btn-outline-secondary"
                               title="Edit">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
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
        $paginator = $tickets;
        $baseUrl   = url('tickets');
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
