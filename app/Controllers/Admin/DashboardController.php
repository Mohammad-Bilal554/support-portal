<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(Request $request): string
    {
        $this->requireLogin();
        $user  = $this->auth();
        $name  = User::fullName($user);
        $roles = ['super_admin'=>'Super Admin','employee'=>'Employee','client'=>'Client'];
        $roleLabel = $roles[$user['role']] ?? ucfirst($user['role']);

        // Gather quick stats (safe defaults if DB not set up)
        $db = \App\Core\Database::getInstance();
        try {
            $totalTickets  = $db->fetchColumn("SELECT COUNT(*) FROM tickets") ?? 0;
            $openTickets   = $db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE status='open'") ?? 0;
            $inProgress    = $db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE status='in_progress'") ?? 0;
            $resolvedToday = $db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE status='resolved' AND DATE(resolved_at)=CURDATE()") ?? 0;
        } catch (\Throwable) {
            $totalTickets = $openTickets = $inProgress = $resolvedToday = '—';
        }

        ob_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= e(env('APP_NAME','Support Portal')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--sidebar-w:260px;--primary:#0d6efd;}
        body{font-family:'Inter',sans-serif;background:#f1f5f9;margin:0;}
        .sidebar{position:fixed;top:0;left:0;bottom:0;width:var(--sidebar-w);background:#0f172a;z-index:100;display:flex;flex-direction:column;overflow-y:auto;}
        .main{margin-left:var(--sidebar-w);min-height:100vh;padding:1.75rem;}
        .brand{padding:1.25rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:0.75rem;text-decoration:none;}
        .brand-icon{width:36px;height:36px;background:var(--primary);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;flex-shrink:0;}
        .brand-text{color:#fff;font-weight:700;font-size:1.05rem;}
        .nav-section{padding:1rem 1rem 0.25rem;font-size:0.68rem;font-weight:600;color:rgba(255,255,255,0.3);letter-spacing:0.8px;text-transform:uppercase;}
        .nav-link{display:flex;align-items:center;gap:0.65rem;color:rgba(255,255,255,0.6);padding:0.55rem 1.25rem;font-size:0.875rem;text-decoration:none;border-radius:0;transition:all 0.15s;border-left:3px solid transparent;}
        .nav-link i{width:18px;text-align:center;font-size:1rem;}
        .nav-link:hover{color:#fff;background:rgba(255,255,255,0.06);}
        .nav-link.active{color:#fff;background:rgba(13,110,253,0.2);border-left-color:var(--primary);}
        .sidebar-footer{margin-top:auto;padding:1rem 1.25rem;border-top:1px solid rgba(255,255,255,0.08);}
        .user-mini{display:flex;align-items:center;gap:0.6rem;}
        .user-mini img{width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.15);}
        .user-mini-info{min-width:0;}
        .user-mini-name{color:#fff;font-size:0.825rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .user-mini-role{color:rgba(255,255,255,0.4);font-size:0.7rem;}
        .topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0.9rem 1.75rem;display:flex;justify-content:space-between;align-items:center;margin:-1.75rem -1.75rem 1.75rem;position:sticky;top:0;z-index:50;}
        .topbar-title{font-weight:700;font-size:1.1rem;color:#0f172a;}
        .stat-card{background:#fff;border-radius:14px;padding:1.4rem 1.5rem;box-shadow:0 1px 8px rgba(0,0,0,0.06);border:1px solid #f1f5f9;transition:transform 0.2s;}
        .stat-card:hover{transform:translateY(-2px);}
        .stat-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;}
        .stat-value{font-size:1.85rem;font-weight:700;color:#0f172a;line-height:1.1;}
        .stat-label{font-size:0.8rem;color:#64748b;margin-top:0.2rem;}
        .module-card{background:#fff;border-radius:14px;padding:1.5rem;box-shadow:0 1px 8px rgba(0,0,0,0.06);border:1px solid #f1f5f9;}
        .module-item{background:#f8fafc;border-radius:8px;padding:0.6rem 0.85rem;font-size:0.8rem;border:1px solid #e2e8f0;display:flex;align-items:center;gap:0.4rem;}
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <a href="<?= url('dashboard') ?>" class="brand">
        <div class="brand-icon"><i class="bi bi-headset"></i></div>
        <span class="brand-text"><?= e(env('APP_NAME','Support Portal')) ?></span>
    </a>

    <div style="flex:1;">
        <div class="nav-section">Main</div>
        <a href="<?= url('dashboard') ?>" class="nav-link active">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <a href="<?= url('tickets') ?>" class="nav-link">
            <i class="bi bi-ticket-perforated-fill"></i> Tickets
        </a>

        <?php if (in_array($user['role'],['super_admin','employee'])): ?>
        <div class="nav-section">Management</div>
        <?php if ($user['role']==='super_admin'): ?>
        <a href="<?= url('admin/users') ?>" class="nav-link">
            <i class="bi bi-people-fill"></i> Users
        </a>
        <a href="<?= url('admin/companies') ?>" class="nav-link">
            <i class="bi bi-building-fill"></i> Companies
        </a>
        <?php endif; ?>
        <a href="<?= url('admin/reports') ?>" class="nav-link">
            <i class="bi bi-bar-chart-fill"></i> Reports
        </a>
        <?php if ($user['role']==='super_admin'): ?>
        <a href="<?= url('admin/settings') ?>" class="nav-link">
            <i class="bi bi-gear-fill"></i> Settings
        </a>
        <a href="<?= url('admin/logs') ?>" class="nav-link">
            <i class="bi bi-journal-text"></i> Activity Logs
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <div class="user-mini">
            <img src="<?= User::avatarUrl($user) ?>" alt="Avatar">
            <div class="user-mini-info">
                <div class="user-mini-name"><?= e($name) ?></div>
                <div class="user-mini-role"><?= e($roleLabel) ?></div>
            </div>
            <a href="<?= url('auth/logout') ?>" class="ms-auto" style="color:rgba(255,255,255,0.4);" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</aside>

<!-- Main -->
<main class="main">
    <div class="topbar">
        <span class="topbar-title">Dashboard</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-pill" style="font-size:0.75rem;"><?= e($roleLabel) ?></span>
            <a href="<?= url('tickets/create') ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>New Ticket
            </a>
        </div>
    </div>

    <!-- Welcome -->
    <div class="mb-4">
        <h5 class="fw-semibold mb-0">Welcome back, <?= e($user['first_name']) ?>! 👋</h5>
        <p class="text-muted mb-0" style="font-size:0.875rem;">Here's what's happening with your support portal.</p>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Total Tickets',  $totalTickets,  'bi-ticket-perforated','bg-primary bg-opacity-10 text-primary'],
            ['Open',           $openTickets,   'bi-folder2-open',     'bg-danger bg-opacity-10 text-danger'],
            ['In Progress',    $inProgress,    'bi-hourglass-split',  'bg-warning bg-opacity-10 text-warning'],
            ['Resolved Today', $resolvedToday, 'bi-check-circle-fill','bg-success bg-opacity-10 text-success'],
        ] as [$label,$value,$icon,$cls]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-value"><?= e((string)$value) ?></div>
                        <div class="stat-label"><?= e($label) ?></div>
                    </div>
                    <div class="stat-icon <?= $cls ?>"><i class="bi <?= $icon ?>"></i></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Module Progress -->
    <div class="module-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-semibold mb-0"><i class="bi bi-check2-circle text-success me-2"></i>Build Progress</h6>
            <span class="badge bg-success">Modules 1 & 2 Complete</span>
        </div>
        <div class="row g-2">
            <?php foreach ([
                ['1','Core Framework',   true],
                ['2','Authentication',   true],
                ['3','UI Layout Shell',  false],
                ['4','User Management',  false],
                ['5','Company Management',false],
                ['6','Ticket System',    false],
                ['7','Conversations',    false],
                ['8','File Attachments', false],
                ['9','Email Notifications',false],
                ['10','Dashboards & Charts',false],
                ['11','Activity Logs',   false],
                ['12','Reports & Export',false],
            ] as [$num,$label,$done]): ?>
            <div class="col-md-4 col-6">
                <div class="module-item">
                    <i class="bi <?= $done?'bi-check-circle-fill text-success':'bi-circle text-muted' ?>"></i>
                    <span><strong>M<?= $num ?>:</strong> <?= e($label) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
        return ob_get_clean();
    }

    public function logs(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());
        return '<div style="padding:2rem;font-family:sans-serif;"><h2>Activity Logs — Coming in Module 11</h2><a href="' . url('dashboard') . '">← Back</a></div>';
    }
}
