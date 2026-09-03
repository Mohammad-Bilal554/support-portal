<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Company;
use App\Services\ReportService;

class ReportController extends Controller
{
    private ReportService $reportService;

    public function __construct()
    {
        parent::__construct();
        $this->reportService = new ReportService();
    }

    // GET /admin/reports
    public function index(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isEmployee());

        $filters = [
            'preset'     => $request->query('preset',     'last_30'),
            'date_from'  => $request->query('date_from',  ''),
            'date_to'    => $request->query('date_to',    ''),
            'status'     => $request->query('status',     ''),
            'priority'   => $request->query('priority',   ''),
            'company_id' => $request->query('company_id', ''),
        ];

        $summary    = $this->reportService->getSummary($filters);
        $byStatus   = $this->reportService->getByStatus($filters);
        $byPriority = $this->reportService->getByPriority($filters);
        $byCategory = $this->reportService->getByCategory($filters);
        $dailyTrend = $this->reportService->getDailyTrend($filters);
        $employees  = $this->reportService->getEmployeePerformance($filters);
        $byCompany  = $this->reportService->getByCompany($filters);
        $companies  = Company::getAllActive();

        return $this->view('admin.reports.index', [
            'title'       => 'Reports & Analytics',
            'summary'     => $summary,
            'byStatus'    => $byStatus,
            'byPriority'  => $byPriority,
            'byCategory'  => $byCategory,
            'dailyTrend'  => $dailyTrend,
            'employees'   => $employees,
            'byCompany'   => $byCompany,
            'companies'   => $companies,
            'filters'     => $filters,
            'breadcrumbs' => [
                ['label' => 'Admin'],
                ['label' => 'Reports'],
            ],
        ]);
    }

    // GET /admin/reports/export/pdf
    public function exportPdf(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isEmployee());

        $filters = [
            'preset'     => $request->query('preset',     'last_30'),
            'date_from'  => $request->query('date_from',  ''),
            'date_to'    => $request->query('date_to',    ''),
            'status'     => $request->query('status',     ''),
            'priority'   => $request->query('priority',   ''),
            'company_id' => $request->query('company_id', ''),
        ];

        try {
            $filePath = $this->reportService->exportPdf($filters);

            if (!file_exists($filePath)) {
                $this->session->error('Failed to generate PDF.');
                $this->redirect(url('admin/reports'));
            }

            $ext      = pathinfo($filePath, PATHINFO_EXTENSION);
            $mime     = $ext === 'pdf' ? 'application/pdf' : 'text/html';
            $fileName = 'ticket-report-' . date('Y-m-d') . '.' . $ext;

            header('Content-Type: ' . $mime);
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: private, no-cache');
            readfile($filePath);
            @unlink($filePath);
            exit;

        } catch (\Throwable $e) {
            \App\Core\Logger::getInstance()->error('PDF export failed: ' . $e->getMessage());
            $this->session->error('PDF export failed: ' . $e->getMessage());
            $this->redirect(url('admin/reports'));
        }
    }

    // GET /admin/reports/export/excel
    public function exportExcel(Request $request): string
    {
        $this->requireLogin();
        $this->authorize($this->isEmployee());

        $filters = [
            'preset'     => $request->query('preset',     'last_30'),
            'date_from'  => $request->query('date_from',  ''),
            'date_to'    => $request->query('date_to',    ''),
            'status'     => $request->query('status',     ''),
            'priority'   => $request->query('priority',   ''),
            'company_id' => $request->query('company_id', ''),
        ];

        try {
            $filePath = $this->reportService->exportExcel($filters);

            if (!file_exists($filePath)) {
                $this->session->error('Failed to generate Excel file.');
                $this->redirect(url('admin/reports'));
            }

            $ext   = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimes = [
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'csv'  => 'text/csv',
            ];
            $mime     = $mimes[$ext] ?? 'application/octet-stream';
            $fileName = 'ticket-report-' . date('Y-m-d') . '.' . $ext;

            header('Content-Type: ' . $mime);
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: private, no-cache');
            readfile($filePath);
            @unlink($filePath);
            exit;

        } catch (\Throwable $e) {
            \App\Core\Logger::getInstance()->error('Excel export failed: ' . $e->getMessage());
            $this->session->error('Excel export failed: ' . $e->getMessage());
            $this->redirect(url('admin/reports'));
        }
    }
}
