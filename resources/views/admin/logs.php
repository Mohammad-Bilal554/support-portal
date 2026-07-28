<?php
/**
 * Activity Logs View
 * Layout: layouts/app
 */
$title = 'Activity Logs';
ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Activity Logs</h1>
        <p class="page-subtitle">Full audit trail of all system actions.</p>
    </div>
</div>

<div class="table-card">
    <div class="card-header">
        <span><i class="bi bi-journal-code me-2 text-primary"></i>Recent Activity</span>
        <span class="text-muted" style="font-size:.8rem;">
            <?= number_format($logs['total'] ?? 0) ?> total entries
        </span>
    </div>

    <?php if (empty($logs['data'])): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="bi bi-journal-x"></i></div>
        <h6>No activity yet</h6>
        <p>System actions will appear here as users interact with the portal.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Description</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs['data'] as $log): ?>
                <tr>
                    <td style="white-space:nowrap;font-size:.78rem;color:var(--text-muted);">
                        <?= htmlspecialchars(format_datetime($log['created_at'])) ?>
                    </td>
                    <td>
                        <?php if ($log['first_name']): ?>
                        <div style="font-size:.825rem;font-weight:500;">
                            <?= htmlspecialchars($log['first_name'].' '.$log['last_name']) ?>
                        </div>
                        <div style="font-size:.75rem;color:var(--text-muted);">
                            <?= htmlspecialchars($log['email'] ?? '') ?>
                        </div>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:.8rem;">System</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $actionColors = [
                            'login'          => ['bg-success bg-opacity-10 text-success', 'bi-box-arrow-in-right'],
                            'logout'         => ['bg-secondary bg-opacity-10 text-secondary', 'bi-box-arrow-left'],
                            'password_reset' => ['bg-warning bg-opacity-10 text-warning', 'bi-shield-lock'],
                            'ticket_created' => ['bg-primary bg-opacity-10 text-primary', 'bi-plus-circle'],
                            'ticket_updated' => ['bg-info bg-opacity-10 text-info', 'bi-pencil'],
                            'ticket_deleted' => ['bg-danger bg-opacity-10 text-danger', 'bi-trash'],
                            'status_changed' => ['bg-purple bg-opacity-10 text-purple', 'bi-arrow-repeat'],
                        ];
                        $ac = $actionColors[$log['action']] ?? ['bg-secondary bg-opacity-10 text-secondary','bi-activity'];
                        ?>
                        <span class="badge <?= $ac[0] ?>" style="font-size:.72rem;gap:.3rem;display:inline-flex;align-items:center;">
                            <i class="bi <?= $ac[1] ?>"></i>
                            <?= htmlspecialchars(ucwords(str_replace('_',' ',$log['action']))) ?>
                        </span>
                    </td>
                    <td style="font-size:.8rem;">
                        <?php if ($log['entity_type']): ?>
                        <code style="font-size:.72rem;background:#f1f5f9;padding:.15rem .4rem;border-radius:4px;">
                            <?= htmlspecialchars($log['entity_type']) ?>
                            <?= $log['entity_id'] ? '#'.$log['entity_id'] : '' ?>
                        </code>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td style="font-size:.82rem;max-width:250px;" class="text-truncate">
                        <?= htmlspecialchars($log['description'] ?? '—') ?>
                    </td>
                    <td style="font-size:.78rem;color:var(--text-muted);font-family:monospace;">
                        <?= htmlspecialchars($log['ip_address'] ?? '—') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="px-3 py-2 border-top">
        <?php
        $baseUrl = url('admin/logs');
        include base_path('resources/views/partials/pagination.php');
        $paginator = $logs;
        ?>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
            <div style="font-size:.8rem;color:var(--text-muted);">
                Showing <?= $logs['from'] ?> to <?= $logs['to'] ?> of <?= number_format($logs['total']) ?> entries
            </div>
            <?= render_pagination($logs, url('admin/logs')) ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
