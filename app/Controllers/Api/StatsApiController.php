<?php
declare(strict_types=1);
namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\Request;
use App\Services\ReportService;

/**
 * Stats API Controller
 *
 * GET /api/v1/stats/summary       Dashboard summary counts
 * GET /api/v1/stats/trend         Daily ticket trend
 * GET /api/v1/stats/by-status     Breakdown by status
 * GET /api/v1/stats/by-priority   Breakdown by priority
 * GET /api/v1/stats/employees     Employee performance
 */
class StatsApiController extends ApiController
{
    private ReportService $reportService;

    public function __construct(Request $request)
    {
        parent::__construct();
        $this->request       = $request;
        $this->reportService = new ReportService();
    }

    // GET /api/v1/stats/summary
    public function summary(Request $request): string
    {
        if (!$this->isEmployee()) return $this->forbidden();

        $filters = ['preset' => $request->query('preset', 'last_30')];
        return $this->success($this->reportService->getSummary($filters));
    }

    // GET /api/v1/stats/trend
    public function trend(Request $request): string
    {
        if (!$this->isEmployee()) return $this->forbidden();

        $filters = ['preset' => $request->query('preset', 'last_30')];
        return $this->success($this->reportService->getDailyTrend($filters));
    }

    // GET /api/v1/stats/by-status
    public function byStatus(Request $request): string
    {
        if (!$this->isEmployee()) return $this->forbidden();

        $filters = ['preset' => $request->query('preset', 'last_30')];
        return $this->success($this->reportService->getByStatus($filters));
    }

    // GET /api/v1/stats/by-priority
    public function byPriority(Request $request): string
    {
        if (!$this->isEmployee()) return $this->forbidden();

        $filters = ['preset' => $request->query('preset', 'last_30')];
        return $this->success($this->reportService->getByPriority($filters));
    }

    // GET /api/v1/stats/employees
    public function employees(Request $request): string
    {
        if (!$this->isAdmin()) return $this->forbidden();

        $filters = ['preset' => $request->query('preset', 'last_30')];
        return $this->success($this->reportService->getEmployeePerformance($filters));
    }
}
