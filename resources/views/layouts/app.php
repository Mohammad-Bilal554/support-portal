<?php
/**
 * Main Authenticated Layout
 *
 * Variables expected:
 *  $title      - Page title
 *  $authUser   - Logged-in user array
 *  $pageHeader - Optional: ['title'=>'', 'subtitle'=>'', 'actions'=>'']
 *  $breadcrumbs- Optional: [['label'=>'','url'=>''], ...]
 *  $content    - Page body (injected by View renderer)
 */

use App\Core\Session;
use App\Core\Csrf;
use App\Models\User;

$session  = Session::getInstance();
$appName  = env('APP_NAME', 'Support Portal');
$appUrl   = env('APP_URL', '');
$user     = $authUser ?? $session->getUser() ?? [];
$role     = $user['role'] ?? 'client';
$fullName = User::fullName($user);
$roleLabels = ['super_admin'=>'Super Admin','employee'=>'Employee','client'=>'Client'];
$roleLabel  = $roleLabels[$role] ?? ucfirst($role);
$avatarUrl  = User::avatarUrl($user);
$pageTitle  = ($title ?? 'Dashboard') . ' — ' . $appName;
$csrfToken  = Csrf::getToken();

// Flash messages
$flashSuccess = $session->getFlash('success');
$flashError   = $session->getFlash('error');
$flashWarning = $session->getFlash('warning');
$flashInfo    = $session->getFlash('info');

// Nav items definition
$navItems = [
    'main' => [
        ['label'=>'Dashboard',  'icon'=>'bi-grid-1x2-fill',          'url'=>url('dashboard'),        'route'=>'dashboard',   'roles'=>['super_admin','employee','client']],
        ['label'=>'Tickets',    'icon'=>'bi-ticket-perforated-fill', 'url'=>url('tickets'),          'route'=>'tickets',     'roles'=>['super_admin','employee','client']],
    ],
    'management' => [
        ['label'=>'Users',      'icon'=>'bi-people-fill',            'url'=>url('admin/users'),      'route'=>'admin/users', 'roles'=>['super_admin']],
        ['label'=>'Companies',  'icon'=>'bi-building-fill',          'url'=>url('admin/companies'),  'route'=>'admin/comp',  'roles'=>['super_admin']],
        ['label'=>'Reports',    'icon'=>'bi-bar-chart-fill',         'url'=>url('admin/reports'),    'route'=>'admin/rep',   'roles'=>['super_admin','employee']],
    ],
    'system' => [
        ['label'=>'Settings',   'icon'=>'bi-gear-fill',              'url'=>url('admin/settings'),   'route'=>'admin/set',   'roles'=>['super_admin']],
        ['label'=>'Logs',       'icon'=>'bi-journal-code',           'url'=>url('admin/logs'),       'route'=>'admin/logs',  'roles'=>['super_admin']],
    ],
];

// Current path for active detection
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

if (!function_exists('navIsActive')) {
    function navIsActive(string $url): bool {
        global $currentPath;
        $curr = is_string($currentPath) ? $currentPath : '/';
        $navPath = parse_url($url, PHP_URL_PATH);
        if (!is_string($navPath) || $navPath === '') {
            return false;
        }
        return str_starts_with($curr, $navPath) && $navPath !== '/';
    }
}

if (!function_exists('canSee')) {
    function canSee(array $item, string $role): bool {
        return isset($item['roles']) && is_array($item['roles']) && in_array($role, $item['roles'], true);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;450;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- App CSS -->
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">

    <?php if (isset($extraCss)) echo $extraCss; ?>
</head>
<body>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="wrapper">

    <!-- ── Sidebar ────────────────────────────────────────────── -->
    <aside class="sidebar" id="sidebar">

        <!-- Brand -->
        <a href="<?= url('dashboard') ?>" class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <i class="bi bi-headset"></i>
            </div>
            <span class="sidebar-brand-text"><?= htmlspecialchars($appName) ?></span>
        </a>

        <!-- Navigation -->
        <nav class="sidebar-nav">

            <!-- Main -->
            <?php if (array_filter($navItems['main'], fn($i) => canSee($i, $role))): ?>
            <div class="nav-section-label">Main</div>
            <?php foreach ($navItems['main'] as $item):
                if (!canSee($item, $role)) continue;
                $active = navIsActive($item['url']) ? 'active' : '';
            ?>
            <a href="<?= htmlspecialchars($item['url']) ?>"
               class="nav-link-item <?= $active ?>"
               data-tooltip="<?= htmlspecialchars($item['label']) ?>">
                <span class="nav-icon"><i class="bi <?= $item['icon'] ?>"></i></span>
                <span class="nav-label"><?= htmlspecialchars($item['label']) ?></span>
                <?php if ($item['label'] === 'Tickets'): ?>
                    <?php
                    // Unread ticket count badge
                    try {
                        $db = \App\Core\Database::getInstance();
                        if ($role === 'client') {
                            $cnt = $db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE created_by=? AND status NOT IN ('closed','resolved')", [$user['id']]);
                        } else {
                            $cnt = $db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE status='open'");
                        }
                        if ($cnt > 0):
                    ?>
                    <span class="badge bg-danger nav-badge"><?= $cnt > 99 ? '99+' : $cnt ?></span>
                    <?php endif; } catch(\Throwable $e) {} ?>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Management -->
            <?php $mgmtItems = array_filter($navItems['management'], fn($i) => canSee($i, $role));
            if ($mgmtItems): ?>
            <div class="nav-section-label">Management</div>
            <?php foreach ($mgmtItems as $item):
                $active = navIsActive($item['url']) ? 'active' : '';
            ?>
            <a href="<?= htmlspecialchars($item['url']) ?>"
               class="nav-link-item <?= $active ?>"
               data-tooltip="<?= htmlspecialchars($item['label']) ?>">
                <span class="nav-icon"><i class="bi <?= $item['icon'] ?>"></i></span>
                <span class="nav-label"><?= htmlspecialchars($item['label']) ?></span>
            </a>
            <?php endforeach; endif; ?>

            <!-- System -->
            <?php $sysItems = array_filter($navItems['system'], fn($i) => canSee($i, $role));
            if ($sysItems): ?>
            <div class="nav-section-label">System</div>
            <?php foreach ($sysItems as $item):
                $active = navIsActive($item['url']) ? 'active' : '';
            ?>
            <a href="<?= htmlspecialchars($item['url']) ?>"
               class="nav-link-item <?= $active ?>"
               data-tooltip="<?= htmlspecialchars($item['label']) ?>">
                <span class="nav-icon"><i class="bi <?= $item['icon'] ?>"></i></span>
                <span class="nav-label"><?= htmlspecialchars($item['label']) ?></span>
            </a>
            <?php endforeach; endif; ?>

        </nav>

        <!-- Sidebar Footer (User) -->
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <img src="<?= htmlspecialchars($avatarUrl) ?>"
                     alt="<?= htmlspecialchars($fullName) ?>"
                     class="sidebar-user-avatar">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= htmlspecialchars($fullName) ?></div>
                    <div class="sidebar-user-role"><?= htmlspecialchars($roleLabel) ?></div>
                </div>
                <div class="sidebar-footer-actions ms-auto">
                    <a href="<?= url('auth/logout') ?>"
                       class="topbar-btn border-0"
                       style="color:rgba(255,255,255,0.4);"
                       title="Logout"
                       data-bs-toggle="tooltip"
                       data-bs-placement="top">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

    </aside>
    <!-- ── End Sidebar ────────────────────────────────────────── -->

    <!-- ── Main Content ───────────────────────────────────────── -->
    <div class="main-content" id="mainContent">

        <!-- Topbar -->
        <header class="topbar">

            <!-- Sidebar Toggle -->
            <button class="topbar-toggle" id="sidebarToggle" title="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>

            <!-- Breadcrumb -->
            <nav class="topbar-breadcrumb" aria-label="breadcrumb">
                <?php if (!empty($breadcrumbs)): ?>
                    <a href="<?= url('dashboard') ?>" style="color:var(--text-muted);text-decoration:none;">
                        <i class="bi bi-house-door-fill" style="font-size:.8rem;"></i>
                    </a>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
                        <?php if (!empty($crumb['url'])): ?>
                            <a href="<?= htmlspecialchars($crumb['url']) ?>" style="color:var(--text-muted);text-decoration:none;"><?= htmlspecialchars($crumb['label']) ?></a>
                        <?php else: ?>
                            <span class="current"><?= htmlspecialchars($crumb['label']) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="current"><?= htmlspecialchars($title ?? 'Dashboard') ?></span>
                <?php endif; ?>
            </nav>

            <!-- Actions -->
            <div class="topbar-actions">

                <!-- New Ticket shortcut -->
                <a href="<?= url('tickets/create') ?>" class="topbar-btn" title="New Ticket" data-bs-toggle="tooltip">
                    <i class="bi bi-plus-lg"></i>
                </a>

                <!-- Notifications -->
                <div class="dropdown">
                    <button class="topbar-btn" id="notifBell" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="notif-dot" style="display:none;"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notif-dropdown p-0" style="border-radius:14px;">
                        <div class="notif-header">
                            <span>Notifications</span>
                            <a href="#" class="text-primary" style="font-size:.8rem;font-weight:500;">Mark all read</a>
                        </div>
                        <div id="notifList">
                            <div class="empty-state py-4">
                                <i class="bi bi-bell-slash" style="font-size:2rem;color:var(--text-muted);display:block;margin-bottom:.5rem;opacity:.4;"></i>
                                <p style="font-size:.8rem;color:var(--text-muted);margin:0;">No new notifications</p>
                            </div>
                        </div>
                        <div class="notif-footer">
                            <a href="#" class="auth-link" style="font-size:.8rem;">View all notifications</a>
                        </div>
                    </div>
                </div>

                <!-- User dropdown -->
                <div class="dropdown">
                    <div class="topbar-user" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= htmlspecialchars($avatarUrl) ?>"
                             alt="<?= htmlspecialchars($fullName) ?>"
                             class="topbar-avatar">
                        <span class="topbar-username"><?= htmlspecialchars($user['first_name'] ?? '') ?></span>
                        <i class="bi bi-chevron-down" style="font-size:.65rem;color:var(--text-muted);"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end" style="min-width:210px;">
                        <li>
                            <div style="padding:.6rem 1rem .4rem;">
                                <div style="font-weight:600;font-size:.875rem;"><?= htmlspecialchars($fullName) ?></div>
                                <div style="font-size:.775rem;color:var(--text-muted);"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                                <span class="badge mt-1" style="font-size:.65rem;background:var(--primary-light);color:var(--primary);"><?= htmlspecialchars($roleLabel) ?></span>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= url('tickets') ?>"><i class="bi bi-ticket-perforated"></i> My Tickets</a></li>
                        <?php if ($role === 'super_admin'): ?>
                        <li><a class="dropdown-item" href="<?= url('admin/settings') ?>"><i class="bi bi-gear"></i> Settings</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger"
                               href="<?= url('auth/logout') ?>"
                               data-confirm="Are you sure you want to log out?"
                               data-confirm-title="Logout">
                                <i class="bi bi-box-arrow-right"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>

            </div>
        </header>
        <!-- End Topbar -->

        <!-- Flash Messages -->
        <div class="px-4 pt-3" id="flashMessages">
            <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" data-auto-dismiss role="alert">
                <i class="bi bi-check-circle-fill flex-shrink-0"></i>
                <div><?= htmlspecialchars($flashSuccess) ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" data-auto-dismiss role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <div><?= htmlspecialchars($flashError) ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if ($flashWarning): ?>
            <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2" data-auto-dismiss role="alert">
                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                <div><?= htmlspecialchars($flashWarning) ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            <?php if ($flashInfo): ?>
            <div class="alert alert-info alert-dismissible fade show d-flex align-items-center gap-2" data-auto-dismiss role="alert">
                <i class="bi bi-info-circle-fill flex-shrink-0"></i>
                <div><?= htmlspecialchars($flashInfo) ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Page Content -->
        <main class="page-container">
            <?= $content ?? '' ?>
        </main>

        <!-- Footer -->
        <footer style="padding:.75rem 1.5rem;border-top:1px solid var(--border-color);background:var(--card-bg);margin-top:auto;">
            <div class="d-flex align-items-center justify-content-between" style="font-size:.775rem;color:var(--text-muted);">
                <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($appName) ?>. All rights reserved.</span>
                <span>v<?= env('APP_VERSION','1.0.0') ?></span>
            </div>
        </footer>

    </div>
    <!-- End Main Content -->

</div><!-- End Wrapper -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js (available for dashboard pages) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- App JS -->
<script src="<?= asset('js/app.js') ?>"></script>

<?php if (isset($extraJs)) echo $extraJs; ?>

</body>
</html>
