<?php
/**
 * Reports & Analytics View
 */
use App\Models\Ticket;
$title = 'Reports & Analytics';
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Reports &amp; Analytics</h1>
        <p class="page-subtitle">
            <?= date('d M Y', strtotime($summary['date_from'])) ?>
            &mdash;
            <?= date('d M Y', strtotime($summary['date_to'])) ?>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('admin/reports/export/pdf?'   . http_build_query($filters)) ?>"
           class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>Export PDF
        </a>
        <a href="<?= url('admin/reports/export/excel?' . http_build_query($filters)) ?>"
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Export Excel
        </a>
    </div>
</div>

<!-- Date Range & Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="<?= url('admin/reports') ?>"
              class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex gap-1 flex-wrap">
                <?php foreach ([
                    'today'      => 'Today',
                    'last_7'     => 'Last 7 days',
                    'last_30'    => 'Last 30 days',
                    'last_90'    => 'Last 90 days',
                    'this_month' => 'This month',
                    'last_month' => 'Last month',
                    'this_year'  => 'This year',
                    'custom'     => 'Custom',
                ] as $val => $label):
                    $active = ($filters['preset'] ?? 'last_30') === $val;
                ?>
                <button type="submit" name="preset" value="<?= $val ?>"
                        class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <?= $label ?>
                </button>
                <?php endforeach; ?>
            </div>

            <?php if (($filters['preset'] ?? '') === 'custom'): ?>
            <div class="d-flex align-items-center gap-2 ms-2">
                <input type="hidden" name="preset" value="custom">
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" style="width:145px;">
                <span class="text-muted small">to</span>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" style="width:145px;">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2 ms-auto flex-wrap">
                <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <?php foreach (Ticket::STATUSES as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($filters['status'] ?? '') === $val ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select name="priority" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                    <option value="">All Priority</option>
                    <?php foreach (Ticket::PRIORITIES as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($filters['priority'] ?? '') === $val ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select name="company_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                    <option value="">All Companies</option>
                    <?php foreach ($companies as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($filters['company_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($filters['status']) || !empty($filters['priority']) || !empty($filters['company_id'])): ?>
                <a href="<?= url('admin/reports?preset=' . ($filters['preset'] ?? 'last_30')) ?>"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Total Tickets',      $summary['total_tickets'],    'bi-ticket-perforated-fill',   'bg-primary bg-opacity-10 text-primary'],
        ['Open',               $summary['open_tickets'],     'bi-folder2-open',              'bg-danger bg-opacity-10 text-danger'],
        ['Resolved / Closed',  $summary['resolved_tickets'], 'bi-check-circle-fill',         'bg-success bg-opacity-10 text-success'],
        ['Avg Resolution',     $summary['avg_resolution_h'].'h','bi-clock-history',          'bg-info bg-opacity-10 text-info'],
        ['Critical',           $summary['critical_tickets'], 'bi-exclamation-triangle-fill', 'bg-dark bg-opacity-10 text-dark'],
    ] as [$label, $value, $icon, $cls]): ?>
    <div class="col-6 col-md-4 col-xl">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value"><?= htmlspecialchars((string)$value) ?></div>
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

<!-- Charts Row 1: Trend + Status Doughnut -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="bi bi-graph-up me-2 text-primary"></i>Daily Ticket Trend</span>
                <div class="d-flex gap-3 align-items-center" style="font-size:.78rem;">
                    <span><span style="display:inline-block;width:12px;height:3px;background:#0d6efd;border-radius:2px;margin-right:4px;vertical-align:middle;"></span>Created</span>
                    <span><span style="display:inline-block;width:12px;height:3px;background:#198754;border-radius:2px;margin-right:4px;vertical-align:middle;"></span>Resolved</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($dailyTrend['labels'])): ?>
                <div class="empty-state py-4">
                    <div class="empty-state-icon"><i class="bi bi-graph-up"></i></div>
                    <h6>No data for this period</h6>
                    <p>Try a wider date range.</p>
                </div>
                <?php else: ?>
                <canvas id="trendChart" height="90"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="bi bi-pie-chart-fill me-2 text-primary"></i>By Status</span>
            </div>
            <div class="card-body d-flex flex-column">
                <?php if (empty($byStatus)): ?>
                <div class="empty-state py-3">
                    <div class="empty-state-icon"><i class="bi bi-pie-chart"></i></div>
                    <h6>No data</h6>
                </div>
                <?php else: ?>
                <canvas id="statusChart" height="170" style="max-height:170px;"></canvas>
                <div class="mt-3">
                    <?php foreach ($byStatus as $s):
                        $pct = $summary['total_tickets'] > 0 ? round($s['count'] / $summary['total_tickets'] * 100) : 0;
                    ?>
                    <div class="d-flex align-items-center justify-content-between mb-1" style="font-size:.78rem;">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:10px;height:10px;border-radius:3px;background:<?= htmlspecialchars($s['color']) ?>;flex-shrink:0;"></div>
                            <span style="color:var(--text-secondary);"><?= htmlspecialchars($s['label']) ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <strong><?= $s['count'] ?></strong>
                            <span class="text-muted" style="width:30px;text-align:right;"><?= $pct ?>%</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2: Priority + Category -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="bi bi-bar-chart-fill me-2 text-primary"></i>By Priority</span>
            </div>
            <div class="card-body">
                <?php if (empty($byPriority)): ?>
                <div class="empty-state py-3"><div class="empty-state-icon"><i class="bi bi-bar-chart"></i></div><h6>No data</h6></div>
                <?php else: ?>
                <canvas id="priorityChart" height="130"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span><i class="bi bi-tags-fill me-2 text-primary"></i>By Category</span>
            </div>
            <div class="card-body">
                <?php if (empty($byCategory)): ?>
                <div class="empty-state py-3"><div class="empty-state-icon"><i class="bi bi-tags"></i></div><h6>No data</h6></div>
                <?php else: ?>
                <canvas id="categoryChart" height="130"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Employee Performance -->
<?php if (!empty($employees)): ?>
<div class="table-card mb-4">
    <div class="card-header">
        <span><i class="bi bi-people-fill me-2 text-primary"></i>Employee Performance</span>
        <span class="text-muted" style="font-size:.8rem;">
            <?= date('d M Y', strtotime($summary['date_from'])) ?> &mdash; <?= date('d M Y', strtotime($summary['date_to'])) ?>
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th class="text-center">Assigned</th>
                    <th class="text-center">Resolved</th>
                    <th class="text-center">Open</th>
                    <th class="text-center">Critical</th>
                    <th class="text-center">Avg Resolution</th>
                    <th style="min-width:160px;">Resolution Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp):
                    $rate     = $emp['total_assigned'] > 0 ? round((int)$emp['resolved'] / (int)$emp['total_assigned'] * 100) : 0;
                    $avgH     = $emp['avg_resolution_hours'] ? round((float)$emp['avg_resolution_hours'], 1) . 'h' : '—';
                    $barColor = $rate >= 70 ? 'bg-success' : ($rate >= 40 ? 'bg-warning' : 'bg-danger');
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= htmlspecialchars(\App\Models\User::avatarUrl($emp)) ?>"
                                 width="34" height="34"
                                 style="border-radius:50%;object-fit:cover;border:2px solid var(--border-color);">
                            <div>
                                <div style="font-weight:600;font-size:.875rem;">
                                    <?= htmlspecialchars(trim($emp['first_name'] . ' ' . $emp['last_name'])) ?>
                                </div>
                                <div style="font-size:.75rem;color:var(--text-muted);">
                                    <?= htmlspecialchars($emp['email']) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= (int)$emp['total_assigned'] ?></span></td>
                    <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success"><?= (int)$emp['resolved'] ?></span></td>
                    <td class="text-center"><span class="badge bg-danger bg-opacity-10 text-danger"><?= (int)$emp['open'] ?></span></td>
                    <td class="text-center"><span class="badge bg-dark bg-opacity-10 text-dark"><?= (int)$emp['critical_handled'] ?></span></td>
                    <td class="text-center" style="font-size:.85rem;"><?= $avgH ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-fill" style="height:6px;border-radius:6px;">
                                <div class="progress-bar <?= $barColor ?>" style="width:<?= $rate ?>%;transition:width .6s ease;"></div>
                            </div>
                            <span style="font-size:.78rem;font-weight:600;width:34px;text-align:right;color:var(--text-secondary);"><?= $rate ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Company Breakdown -->
<?php if (!empty($byCompany)): ?>
<div class="table-card mb-4">
    <div class="card-header">
        <span><i class="bi bi-building-fill me-2 text-primary"></i>Company Breakdown</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Open</th>
                    <th class="text-center">Resolved</th>
                    <th class="text-center">Critical</th>
                    <th class="text-center">Avg Resolution</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byCompany as $c): ?>
                <tr>
                    <td style="font-weight:600;font-size:.875rem;"><?= htmlspecialchars($c['company_name']) ?></td>
                    <td class="text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= (int)$c['total'] ?></span></td>
                    <td class="text-center">
                        <?php if ((int)$c['open'] > 0): ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger"><?= (int)$c['open'] ?></span>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success"><?= (int)$c['resolved'] ?></span></td>
                    <td class="text-center">
                        <?php if ((int)$c['critical'] > 0): ?>
                        <span class="badge bg-dark bg-opacity-10 text-dark"><?= (int)$c['critical'] ?></span>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td class="text-center" style="font-size:.85rem;">
                        <?= $c['avg_resolution_hours'] ? round((float)$c['avg_resolution_hours'], 1) . 'h' : '—' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Export Card -->
<div class="card">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h6 class="fw-bold mb-1"><i class="bi bi-download me-2 text-primary"></i>Export Full Report</h6>
                <p class="text-muted mb-0" style="font-size:.825rem;">
                    Download the complete report for
                    <?= date('d M Y', strtotime($summary['date_from'])) ?> &mdash; <?= date('d M Y', strtotime($summary['date_to'])) ?>.
                    Includes all tickets, employee performance, and company breakdown.
                </p>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <a href="<?= url('admin/reports/export/pdf?'   . http_build_query($filters)) ?>" class="btn btn-danger me-2">
                    <i class="bi bi-file-earmark-pdf-fill me-2"></i>Download PDF
                </a>
                <a href="<?= url('admin/reports/export/excel?' . http_build_query($filters)) ?>" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel-fill me-2"></i>Download Excel
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$trendLabels   = json_encode($dailyTrend['labels']   ?? []);
$trendCreated  = json_encode($dailyTrend['created']  ?? []);
$trendResolved = json_encode($dailyTrend['resolved'] ?? []);
$statusLabels  = json_encode(array_column($byStatus,   'label'));
$statusCounts  = json_encode(array_column($byStatus,   'count'));
$statusColors  = json_encode(array_column($byStatus,   'color'));
$priLabels     = json_encode(array_column($byPriority, 'label'));
$priCounts     = json_encode(array_column($byPriority, 'count'));
$priColors     = json_encode(array_column($byPriority, 'color'));
$catLabels     = json_encode(array_column($byCategory, 'name'));
$catCounts     = json_encode(array_column($byCategory, 'count'));
$catColors     = json_encode(array_column($byCategory, 'color'));

$extraJs = <<<JSCODE
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tt = {
        backgroundColor:'#0f172a',titleColor:'#e2e8f0',bodyColor:'#94a3b8',
        borderColor:'#1e293b',borderWidth:1,padding:10,cornerRadius:8
    };

    // Trend
    const trendEl = document.getElementById('trendChart');
    if (trendEl) {
        new Chart(trendEl, {
            type:'line',
            data:{
                labels:{$trendLabels},
                datasets:[
                    {label:'Created',data:{$trendCreated},borderColor:'#0d6efd',backgroundColor:'rgba(13,110,253,0.07)',borderWidth:2.5,fill:true,tension:0.4,pointRadius:4,pointBackgroundColor:'#0d6efd',pointBorderColor:'#fff',pointBorderWidth:2},
                    {label:'Resolved',data:{$trendResolved},borderColor:'#198754',backgroundColor:'rgba(25,135,84,0.06)',borderWidth:2.5,fill:true,tension:0.4,pointRadius:4,pointBackgroundColor:'#198754',pointBorderColor:'#fff',pointBorderWidth:2}
                ]
            },
            options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false},tooltip:tt},
                scales:{x:{grid:{display:false},ticks:{font:{size:10},color:'#94a3b8'}},y:{grid:{color:'#f1f5f9'},ticks:{font:{size:10},color:'#94a3b8',stepSize:1},beginAtZero:true}}}
        });
    }

    // Status Doughnut
    const statusEl = document.getElementById('statusChart');
    if (statusEl) {
        new Chart(statusEl, {
            type:'doughnut',
            data:{labels:{$statusLabels},datasets:[{data:{$statusCounts},backgroundColor:{$statusColors},borderWidth:3,borderColor:'#fff'}]},
            options:{responsive:true,maintainAspectRatio:false,cutout:'70%',plugins:{legend:{display:false},tooltip:tt}}
        });
    }

    // Priority Bar
    const priEl = document.getElementById('priorityChart');
    if (priEl) {
        new Chart(priEl, {
            type:'bar',
            data:{labels:{$priLabels},datasets:[{data:{$priCounts},backgroundColor:{$priColors},borderRadius:8,borderSkipped:false}]},
            options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false},tooltip:tt},
                scales:{x:{grid:{display:false},ticks:{font:{size:11},color:'#374151'}},y:{grid:{color:'#f1f5f9'},ticks:{font:{size:10},color:'#94a3b8',stepSize:1},beginAtZero:true}}}
        });
    }

    // Category Horizontal Bar
    const catEl = document.getElementById('categoryChart');
    if (catEl) {
        const cc = {$catColors};
        new Chart(catEl, {
            type:'bar',
            data:{labels:{$catLabels},datasets:[{data:{$catCounts},backgroundColor:cc.map(c=>(c||'#6c757d')+'99'),borderColor:cc.map(c=>c||'#6c757d'),borderWidth:2,borderRadius:6,borderSkipped:false}]},
            options:{responsive:true,maintainAspectRatio:true,indexAxis:'y',plugins:{legend:{display:false},tooltip:tt},
                scales:{x:{grid:{color:'#f1f5f9'},ticks:{font:{size:10},color:'#94a3b8',stepSize:1},beginAtZero:true},y:{grid:{display:false},ticks:{font:{size:11},color:'#374151'}}}}
        });
    }
});
</script>
JSCODE;

$content = ob_get_clean();
ob_start();
require base_path('resources/views/layouts/app.php');
echo ob_get_clean();
