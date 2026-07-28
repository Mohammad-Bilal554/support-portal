<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\User;

class DashboardController extends Controller
{
    private Database $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    // GET /dashboard
    public function index(Request $request): string
    {
        $this->requireLogin();
        $user = $this->auth();
        $role = $user['role'];

        // ── Stat Cards ──────────────────────────────────────────
        $stats = $this->getStatCards($role, (int)$user['id'], $user['company_id'] ?? null);

        // ── Status distribution (for doughnut chart) ────────────
        $statusCounts = $this->getStatusCounts($role, (int)$user['id'], $user['company_id'] ?? null);

        // ── Chart data (7-day trend) ─────────────────────────────
        $chartData = $this->getChartData($role, (int)$user['id']);

        // ── Recent tickets ───────────────────────────────────────
        $recentTickets = $this->getRecentTickets($role, (int)$user['id'], $user['company_id'] ?? null);

        // ── Employee performance (admin only) ────────────────────
        $employeeStats = [];
        if ($role === 'super_admin') {
            $employeeStats = $this->getEmployeeStats();
        }

        // ── Quick stats list ─────────────────────────────────────
        $quickStats = $this->getQuickStats($role, (int)$user['id'], $user['company_id'] ?? null);

        return $this->view('admin.dashboard', [
            'title'         => 'Dashboard',
            'stats'         => $stats,
            'statusCounts'  => $statusCounts,
            'chartData'     => $chartData,
            'recentTickets' => $recentTickets,
            'employeeStats' => $employeeStats,
            'quickStats'    => $quickStats,
            'breadcrumbs'   => [['label' => 'Dashboard']],
        ]);
    }

    // GET /admin/logs
    public function logs(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $page = max(1, (int)($request->query('page', 1)));
        $logs = $this->db->paginate(
            "SELECT l.*, u.first_name, u.last_name, u.email
             FROM activity_logs l
             LEFT JOIN users u ON u.id = l.user_id
             ORDER BY l.created_at DESC",
            [], $page, 30
        );

        return $this->view('admin.logs', [
            'title'      => 'Activity Logs',
            'logs'       => $logs,
            'breadcrumbs'=> [['label'=>'System'],['label'=>'Activity Logs']],
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────

    private function getStatCards(string $role, int $userId, ?int $companyId): array
    {
        try {
            if ($role === 'client') {
                $cond   = "WHERE created_by = {$userId}";
                $total  = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets {$cond}");
                $open   = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets {$cond} AND status='open'");
                $prog   = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets {$cond} AND status='in_progress'");
                $res    = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets {$cond} AND status='resolved'");
                return [
                    ['label'=>'My Tickets',    'value'=>$total, 'icon'=>'bi-ticket-perforated-fill','color'=>'bg-primary bg-opacity-10 text-primary'],
                    ['label'=>'Open',           'value'=>$open,  'icon'=>'bi-folder2-open',           'color'=>'bg-danger bg-opacity-10 text-danger'],
                    ['label'=>'In Progress',    'value'=>$prog,  'icon'=>'bi-hourglass-split',        'color'=>'bg-warning bg-opacity-10 text-warning'],
                    ['label'=>'Resolved',       'value'=>$res,   'icon'=>'bi-check-circle-fill',      'color'=>'bg-success bg-opacity-10 text-success'],
                ];
            }

            if ($role === 'employee') {
                $total  = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE assigned_to = {$userId}");
                $open   = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE assigned_to = {$userId} AND status IN ('assigned','in_progress')");
                $res    = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE assigned_to = {$userId} AND status='resolved'");
                $unass  = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE status='open'");
                return [
                    ['label'=>'Assigned to Me', 'value'=>$total, 'icon'=>'bi-person-check-fill',      'color'=>'bg-primary bg-opacity-10 text-primary'],
                    ['label'=>'Active',          'value'=>$open,  'icon'=>'bi-hourglass-split',        'color'=>'bg-warning bg-opacity-10 text-warning'],
                    ['label'=>'Resolved by Me',  'value'=>$res,   'icon'=>'bi-check-circle-fill',      'color'=>'bg-success bg-opacity-10 text-success'],
                    ['label'=>'Unassigned',      'value'=>$unass, 'icon'=>'bi-inbox-fill',             'color'=>'bg-danger bg-opacity-10 text-danger'],
                ];
            }

            // super_admin
            $total  = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets");
            $open   = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE status='open'");
            $prog   = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE status='in_progress'");
            $res    = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE status='resolved' AND DATE(resolved_at)=CURDATE()");
            $users  = $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE is_active=1");
            return [
                ['label'=>'Total Tickets',   'value'=>$total, 'icon'=>'bi-ticket-perforated-fill','color'=>'bg-primary bg-opacity-10 text-primary'],
                ['label'=>'Open',            'value'=>$open,  'icon'=>'bi-folder2-open',           'color'=>'bg-danger bg-opacity-10 text-danger'],
                ['label'=>'In Progress',     'value'=>$prog,  'icon'=>'bi-hourglass-split',        'color'=>'bg-warning bg-opacity-10 text-warning'],
                ['label'=>'Resolved Today',  'value'=>$res,   'icon'=>'bi-check-circle-fill',      'color'=>'bg-success bg-opacity-10 text-success'],
            ];

        } catch (\Throwable $e) {
            return [
                ['label'=>'Total Tickets',  'value'=>'—','icon'=>'bi-ticket-perforated-fill','color'=>'bg-primary bg-opacity-10 text-primary'],
                ['label'=>'Open',           'value'=>'—','icon'=>'bi-folder2-open',          'color'=>'bg-danger bg-opacity-10 text-danger'],
                ['label'=>'In Progress',    'value'=>'—','icon'=>'bi-hourglass-split',       'color'=>'bg-warning bg-opacity-10 text-warning'],
                ['label'=>'Resolved Today', 'value'=>'—','icon'=>'bi-check-circle-fill',     'color'=>'bg-success bg-opacity-10 text-success'],
            ];
        }
    }

    private function getStatusCounts(string $role, int $userId, ?int $companyId): array
    {
        $colorMap = [
            'open'               => '#dc3545',
            'assigned'           => '#fd7e14',
            'in_progress'        => '#0d6efd',
            'waiting_for_client' => '#20c997',
            'resolved'           => '#198754',
            'closed'             => '#6c757d',
        ];

        try {
            $cond = match($role) {
                'client'   => "WHERE created_by = {$userId}",
                'employee' => "WHERE assigned_to = {$userId}",
                default    => '',
            };

            $rows = $this->db->fetchAll(
                "SELECT status, COUNT(*) as count FROM tickets {$cond} GROUP BY status ORDER BY count DESC"
            );

            return array_map(fn($r) => [
                'status' => $r['status'],
                'count'  => (int)$r['count'],
                'color'  => $colorMap[$r['status']] ?? '#6c757d',
            ], $rows);

        } catch (\Throwable) {
            return [];
        }
    }

    private function getChartData(string $role, int $userId): array
    {
        $labels   = [];
        $newData  = [];
        $resData  = [];

        try {
            for ($i = 6; $i >= 0; $i--) {
                $date     = date('Y-m-d', strtotime("-{$i} days"));
                $labels[] = date('D d', strtotime($date));

                $cond = $role === 'employee' ? " AND assigned_to = {$userId}" : '';

                $newData[] = (int)$this->db->fetchColumn(
                    "SELECT COUNT(*) FROM tickets WHERE DATE(created_at)=?{$cond}", [$date]
                );
                $resData[] = (int)$this->db->fetchColumn(
                    "SELECT COUNT(*) FROM tickets WHERE DATE(resolved_at)=? AND status IN ('resolved','closed'){$cond}", [$date]
                );
            }
        } catch (\Throwable) {
            $labels  = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            $newData = $resData = array_fill(0, 7, 0);
        }

        return ['labels' => $labels, 'new' => $newData, 'resolved' => $resData];
    }

    private function getRecentTickets(string $role, int $userId, ?int $companyId): array
    {
        try {
            $cond = match($role) {
                'client'   => "AND t.created_by = {$userId}",
                'employee' => "AND t.assigned_to = {$userId}",
                default    => '',
            };

            return $this->db->fetchAll(
                "SELECT t.*, tc.name as category_name,
                        u.first_name, u.last_name
                 FROM tickets t
                 LEFT JOIN ticket_categories tc ON tc.id = t.category_id
                 LEFT JOIN users u ON u.id = t.created_by
                 WHERE 1=1 {$cond}
                 ORDER BY t.created_at DESC
                 LIMIT 8"
            );
        } catch (\Throwable) {
            return [];
        }
    }

    private function getEmployeeStats(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT u.id, u.first_name, u.last_name, u.avatar,
                        COUNT(t.id) as resolved
                 FROM users u
                 LEFT JOIN tickets t ON t.assigned_to = u.id AND t.status IN ('resolved','closed')
                 WHERE u.role IN ('employee','super_admin') AND u.is_active = 1
                 GROUP BY u.id, u.first_name, u.last_name, u.avatar
                 ORDER BY resolved DESC
                 LIMIT 5"
            );
        } catch (\Throwable) {
            return [];
        }
    }

    private function getQuickStats(string $role, int $userId, ?int $companyId): array
    {
        try {
            if ($role === 'client') {
                $waiting = $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM tickets WHERE created_by=? AND status='waiting_for_client'",
                    [$userId]
                );
                $closed  = $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM tickets WHERE created_by=? AND status='closed'",
                    [$userId]
                );
                return [
                    ['label'=>'Waiting for Your Reply', 'value'=>$waiting, 'dot'=>'#fd7e14'],
                    ['label'=>'Closed Tickets',          'value'=>$closed,  'dot'=>'#6c757d'],
                ];
            }

            $critical   = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE priority='critical' AND status NOT IN ('resolved','closed')");
            $unassigned = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE status='open' AND assigned_to IS NULL");
            $overdue    = $this->db->fetchColumn("SELECT COUNT(*) FROM tickets WHERE due_date < CURDATE() AND status NOT IN ('resolved','closed')");
            $companies  = $role === 'super_admin' ? $this->db->fetchColumn("SELECT COUNT(*) FROM companies WHERE is_active=1") : null;

            $list = [
                ['label'=>'Critical Priority Open', 'value'=>$critical,   'dot'=>'#dc3545'],
                ['label'=>'Unassigned Tickets',     'value'=>$unassigned, 'dot'=>'#fd7e14'],
                ['label'=>'Overdue Tickets',        'value'=>$overdue,    'dot'=>'#6f42c1'],
            ];

            if ($companies !== null) {
                $list[] = ['label'=>'Active Companies', 'value'=>$companies, 'dot'=>'#0d6efd'];
            }

            return $list;

        } catch (\Throwable) {
            return [];
        }
    }
}
