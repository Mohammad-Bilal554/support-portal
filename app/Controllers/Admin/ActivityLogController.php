<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

class ActivityLogController extends Controller
{
    private Database $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    // GET /admin/logs
    public function index(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isAdmin());

        $filters = [
            'search'      => $request->query('search',      ''),
            'action'      => $request->query('action',      ''),
            'user_id'     => $request->query('user_id',     ''),
            'entity_type' => $request->query('entity_type', ''),
            'date_from'   => $request->query('date_from',   ''),
            'date_to'     => $request->query('date_to',     ''),
        ];

        $page    = max(1, $request->integer('page', 1));
        $perPage = 30;

        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $s        = '%' . $filters['search'] . '%';
            $where[]  = "(l.description LIKE ? OR l.action LIKE ? OR u.email LIKE ?)";
            $params   = array_merge($params, [$s, $s, $s]);
        }
        if (!empty($filters['action'])) {
            $where[]  = "l.action = ?";
            $params[] = $filters['action'];
        }
        if (!empty($filters['user_id'])) {
            $where[]  = "l.user_id = ?";
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['entity_type'])) {
            $where[]  = "l.entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = "DATE(l.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = "DATE(l.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql = "SELECT l.*,
                       u.first_name, u.last_name, u.email, u.role, u.avatar
                FROM activity_logs l
                LEFT JOIN users u ON u.id = l.user_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY l.created_at DESC";

        $logs = $this->db->paginate($sql, $params, $page, $perPage);

        // Get distinct actions for filter dropdown
        $actions = $this->db->fetchAll(
            "SELECT DISTINCT action FROM activity_logs ORDER BY action ASC"
        );

        // Get distinct entity types
        $entityTypes = $this->db->fetchAll(
            "SELECT DISTINCT entity_type FROM activity_logs WHERE entity_type != '' ORDER BY entity_type ASC"
        );

        // Get users for filter dropdown
        $users = $this->db->fetchAll(
            "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email
             FROM activity_logs l
             JOIN users u ON u.id = l.user_id
             ORDER BY u.first_name ASC"
        );

        // Summary counts
        $summary = [
            'today'       => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()"),
            'this_week'   => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
            'this_month'  => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
            'total'       => (int)$this->db->fetchColumn("SELECT COUNT(*) FROM activity_logs"),
        ];

        return $this->view('admin.logs.index', [
            'title'       => 'Activity Logs',
            'logs'        => $logs,
            'filters'     => $filters,
            'actions'     => $actions,
            'entityTypes' => $entityTypes,
            'users'       => $users,
            'summary'     => $summary,
            'breadcrumbs' => [
                ['label' => 'Admin'],
                ['label' => 'Activity Logs'],
            ],
        ]);
    }
}
