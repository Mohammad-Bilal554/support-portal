<?php
/**
 * Dashboard View
 * Layout: layouts/app
 */
$title       = 'Dashboard';
$breadcrumbs = [['label'=>'Dashboard']];
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?= htmlspecialchars($authUser['first_name'] ?? '') ?>! Here's what's happening.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('tickets/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Ticket
        </a>
        <?php if (in_array($authUser['role']??'',['super_admin','employee'])): ?>
        <a href="<?= url('admin/reports') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-download"></i> Reports
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Stat Cards ──────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <?php foreach ($stats as $stat): ?>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= htmlspecialchars((string)$stat['value']) ?></div>
                    <div class="stat-label"><?= htmlspecialchars($stat['label']) ?></div>
                    <?php if (!empty($stat['change'])): ?>
                    <div class="stat-change <?= ($stat['change'] >= 0) ? 'up' : 'down' ?>">
                        <i class="bi bi-arrow-<?= ($stat['change'] >= 0) ? 'up' : 'down' ?>-short"></i>
                        <?= abs($stat['change']) ?>% vs last week
                    </div>
                    <?php endif; ?>
                </div>
                <div class="stat-icon <?= htmlspecialchars($stat['color']) ?>">
                    <i class="bi <?= htmlspecialchars($stat['icon']) ?>"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Charts Row ─────────────────────────────────────────────── -->
<?php if (in_array($authUser['role']??'',['super_admin','employee'])): ?>
<div class="row g-3 mb-4">
    <!-- Ticket Trend Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="bi bi-graph-up me-2 text-primary"></i>Ticket Trend (Last 7 Days)</span>
                <div class="d-flex gap-2">
                    <span class="badge" style="background:var(--primary-light);color:var(--primary);font-size:.7rem;">
                        <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>New
                    </span>
                    <span class="badge" style="background:#f0fdf4;color:#166534;font-size:.7rem;">
                        <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Resolved
                    </span>
                </div>
            </div>
            <div class="card-body">
                <canvas id="ticketTrendChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="bi bi-pie-chart me-2 text-primary"></i>Status Distribution</span>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <canvas id="statusChart" height="160" style="max-height:160px;"></canvas>
                <!-- Legend -->
                <div class="mt-3 w-100">
                    <?php foreach ($statusCounts as $s): ?>
                    <div class="d-flex align-items-center justify-content-between mb-1" style="font-size:.78rem;">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:10px;height:10px;border-radius:3px;background:<?= htmlspecialchars($s['color']) ?>;flex-shrink:0;"></div>
                            <span style="color:var(--text-secondary);"><?= htmlspecialchars(ucwords(str_replace('_',' ',$s['status']))) ?></span>
                        </div>
                        <strong style="color:var(--text-primary);"><?= $s['count'] ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Bottom Row ─────────────────────────────────────────────── -->
<div class="row g-3">

    <!-- Recent Tickets -->
    <div class="col-lg-7">
        <div class="table-card">
            <div class="card-header">
                <span><i class="bi bi-ticket-perforated me-2 text-primary"></i>Recent Tickets</span>
                <a href="<?= url('tickets') ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <?php if (empty($recentTickets)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-ticket-perforated"></i></div>
                <h6>No tickets yet</h6>
                <p>Create your first support ticket to get started.</p>
                <a href="<?= url('tickets/create') ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>New Ticket
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTickets as $t): ?>
                        <tr style="cursor:pointer;" onclick="window.location='<?= url('tickets/'.$t['id']) ?>'">
                            <td>
                                <code style="font-size:.75rem;color:var(--text-muted);"><?= htmlspecialchars($t['ticket_number']) ?></code>
                            </td>
                            <td>
                                <div style="font-weight:500;max-width:200px;" class="text-truncate">
                                    <?= htmlspecialchars($t['subject']) ?>
                                </div>
                                <?php if (!empty($t['category_name'])): ?>
                                <small style="color:var(--text-muted);font-size:.72rem;"><?= htmlspecialchars($t['category_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= ticket_status_badge($t['status']) ?></td>
                            <td><?= ticket_priority_badge($t['priority']) ?></td>
                            <td style="font-size:.8rem;color:var(--text-muted);white-space:nowrap;">
                                <?= time_ago($t['created_at']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-5 d-flex flex-column gap-3">

        <!-- Employee Performance (admin/employee only) -->
        <?php if (!empty($employeeStats) && in_array($authUser['role']??'',['super_admin'])): ?>
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-people me-2 text-primary"></i>Top Performers</span>
            </div>
            <div class="card-body p-0">
                <?php foreach ($employeeStats as $i => $emp): ?>
                <div class="d-flex align-items-center gap-3 px-3 py-2 <?= $i < count($employeeStats)-1 ? 'border-bottom' : '' ?>">
                    <img src="<?= htmlspecialchars(\App\Models\User::avatarUrl($emp)) ?>"
                         style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;font-size:.825rem;"><?= htmlspecialchars(\App\Models\User::fullName($emp)) ?></div>
                        <div class="progress mt-1" style="height:4px;border-radius:4px;">
                            <?php $pct = $employeeStats[0]['resolved'] > 0 ? round($emp['resolved'] / $employeeStats[0]['resolved'] * 100) : 0; ?>
                            <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-weight:700;font-size:.9rem;color:var(--text-primary);"><?= $emp['resolved'] ?></div>
                        <div style="font-size:.7rem;color:var(--text-muted);">resolved</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Stats / Activity -->
        <div class="card flex-fill">
            <div class="card-header">
                <span><i class="bi bi-activity me-2 text-primary"></i>Quick Overview</span>
            </div>
            <div class="card-body">
                <?php foreach ($quickStats as $qs): ?>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:8px;height:8px;border-radius:50%;background:<?= htmlspecialchars($qs['dot']) ?>;flex-shrink:0;"></div>
                        <span style="font-size:.845rem;color:var(--text-secondary);"><?= htmlspecialchars($qs['label']) ?></span>
                    </div>
                    <strong style="font-size:.875rem;"><?= htmlspecialchars((string)$qs['value']) ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<!-- ── Chart.js Scripts ────────────────────────────────────────── -->
<?php if (in_array($authUser['role']??'',['super_admin','employee'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Ticket Trend Line Chart
    const trendCtx = document.getElementById('ticketTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartData['labels'] ?? []) ?>,
                datasets: [
                    {
                        label: 'New Tickets',
                        data: <?= json_encode($chartData['new'] ?? []) ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.08)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#0d6efd',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                    },
                    {
                        label: 'Resolved',
                        data: <?= json_encode($chartData['resolved'] ?? []) ?>,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25,135,84,0.06)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#198754',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#e2e8f0',
                        bodyColor: '#94a3b8',
                        borderColor: '#1e293b',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8' } },
                    y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 }, color: '#94a3b8', stepSize: 1 }, beginAtZero: true }
                }
            }
        });
    }

    // Status Doughnut Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        const statusData   = <?= json_encode(array_column($statusCounts,'count')) ?>;
        const statusColors = <?= json_encode(array_column($statusCounts,'color')) ?>;
        const statusLabels = <?= json_encode(array_map(fn($s)=>ucwords(str_replace('_',' ',$s['status'])),$statusCounts)) ?>;

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: statusColors,
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverBorderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#e2e8f0',
                        bodyColor: '#94a3b8',
                        padding: 10,
                        cornerRadius: 8,
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
$__output = ob_get_clean();
echo $__output;
