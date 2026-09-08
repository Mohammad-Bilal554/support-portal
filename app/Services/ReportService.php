<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

class ReportService
{
    private Database $db;
    private Logger   $logger;

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->logger = Logger::getInstance();
    }

    public function getSummary(array $filters = []): array
    {
        [$from, $to] = $this->getDateRange($filters);
        $p    = [$from, $to];
        $row  = $this->db->fetchOne(
            "SELECT COUNT(*) as total_tickets,
                    SUM(CASE WHEN status='open' THEN 1 ELSE 0 END) as open_tickets,
                    SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) as resolved_tickets,
                    SUM(CASE WHEN priority='critical' THEN 1 ELSE 0 END) as critical_tickets
             FROM tickets WHERE created_at BETWEEN ? AND ?",
            $p
        ) ?? [];

        return [
            'total_tickets'    => (int)($row['total_tickets'] ?? 0),
            'open_tickets'     => (int)($row['open_tickets'] ?? 0),
            'resolved_tickets' => (int)($row['resolved_tickets'] ?? 0),
            'avg_resolution_h' => $this->getAvgResolutionHours($from, $to),
            'critical_tickets' => (int)($row['critical_tickets'] ?? 0),
            'date_from'        => $from,
            'date_to'          => $to,
        ];
    }

    public function getByStatus(array $filters = []): array
    {
        [$from, $to] = $this->getDateRange($filters);
        $rows = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count FROM tickets
             WHERE created_at BETWEEN ? AND ?
             GROUP BY status ORDER BY count DESC",
            [$from, $to]
        );
        $colors = [
            'open'               => '#dc3545',
            'assigned'           => '#fd7e14',
            'in_progress'        => '#0d6efd',
            'waiting_for_client' => '#20c997',
            'resolved'           => '#198754',
            'closed'             => '#6c757d',
        ];
        return array_map(fn($r) => [
            'status' => $r['status'],
            'label'  => ucwords(str_replace('_', ' ', $r['status'])),
            'count'  => (int)$r['count'],
            'color'  => $colors[$r['status']] ?? '#6c757d',
        ], $rows);
    }

    public function getByPriority(array $filters = []): array
    {
        [$from, $to] = $this->getDateRange($filters);
        $rows = $this->db->fetchAll(
            "SELECT priority, COUNT(*) as count FROM tickets
             WHERE created_at BETWEEN ? AND ?
             GROUP BY priority ORDER BY FIELD(priority,'critical','high','medium','low')",
            [$from, $to]
        );
        $colors = ['low'=>'#198754','medium'=>'#fd7e14','high'=>'#dc3545','critical'=>'#1e293b'];
        return array_map(fn($r) => [
            'priority' => $r['priority'],
            'label'    => ucfirst($r['priority']),
            'count'    => (int)$r['count'],
            'color'    => $colors[$r['priority']] ?? '#6c757d',
        ], $rows);
    }

    public function getByCategory(array $filters = []): array
    {
        [$from, $to] = $this->getDateRange($filters);
        return $this->db->fetchAll(
            "SELECT tc.name, tc.color, COUNT(t.id) as count
             FROM ticket_categories tc
             LEFT JOIN tickets t ON t.category_id = tc.id AND t.created_at BETWEEN ? AND ?
             WHERE tc.is_active = 1
             GROUP BY tc.id, tc.name, tc.color ORDER BY count DESC",
            [$from, $to]
        );
    }

    public function getDailyTrend(array $filters = []): array
    {
        [$from, $to] = $this->getDateRange($filters);
        $rows = $this->db->fetchAll(
            "SELECT DATE(created_at) AS date, COUNT(*) AS created,
                    SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) AS resolved
             FROM tickets WHERE created_at BETWEEN ? AND ?
             GROUP BY DATE(created_at) ORDER BY date ASC",
            [$from, $to]
        );
        $labels = $created = $resolved = [];
        foreach ($rows as $r) {
            $labels[]   = date('d M', strtotime($r['date']));
            $created[]  = (int)$r['created'];
            $resolved[] = (int)$r['resolved'];
        }
        return compact('labels', 'created', 'resolved');
    }

    public function getEmployeePerformance(array $filters = []): array
    {
        [$from, $to] = $this->getDateRange($filters);
        return $this->db->fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.email, u.avatar,
                    COUNT(t.id) AS total_assigned,
                    SUM(CASE WHEN t.status IN ('resolved','closed') THEN 1 ELSE 0 END) AS resolved,
                    SUM(CASE WHEN t.status = 'open'        THEN 1 ELSE 0 END) AS open,
                    SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN t.priority = 'critical'  THEN 1 ELSE 0 END) AS critical_handled,
                    AVG(CASE WHEN t.resolved_at IS NOT NULL
                        THEN TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) ELSE NULL END) AS avg_resolution_hours
             FROM users u
             LEFT JOIN tickets t ON t.assigned_to = u.id AND t.created_at BETWEEN ? AND ?
             WHERE u.role IN ('super_admin','employee') AND u.is_active = 1
             GROUP BY u.id, u.first_name, u.last_name, u.email, u.avatar
             ORDER BY resolved DESC",
            [$from, $to]
        );
    }

    public function getByCompany(array $filters = []): array
    {
        [$from, $to] = $this->getDateRange($filters);
        return $this->db->fetchAll(
            "SELECT c.name AS company_name,
                    COUNT(t.id) AS total,
                    SUM(CASE WHEN t.status = 'open'                        THEN 1 ELSE 0 END) AS open,
                    SUM(CASE WHEN t.status IN ('resolved','closed')         THEN 1 ELSE 0 END) AS resolved,
                    SUM(CASE WHEN t.priority = 'critical'                  THEN 1 ELSE 0 END) AS critical,
                    AVG(CASE WHEN t.resolved_at IS NOT NULL
                        THEN TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) ELSE NULL END) AS avg_resolution_hours
             FROM companies c
             LEFT JOIN tickets t ON t.company_id = c.id AND t.created_at BETWEEN ? AND ?
             WHERE c.is_active = 1 GROUP BY c.id, c.name ORDER BY total DESC",
            [$from, $to]
        );
    }

    public function getTicketList(array $filters = []): array
    {
        [$from, $to] = $this->getDateRange($filters);
        $where  = ["t.created_at BETWEEN ? AND ?"];
        $params = [$from, $to];
        if (!empty($filters['status']))     { $where[] = "t.status = ?";     $params[] = $filters['status']; }
        if (!empty($filters['priority']))   { $where[] = "t.priority = ?";   $params[] = $filters['priority']; }
        if (!empty($filters['company_id'])) { $where[] = "t.company_id = ?"; $params[] = $filters['company_id']; }

        return $this->db->fetchAll(
            "SELECT t.ticket_number, t.subject, t.status, t.priority,
                    t.created_at, t.resolved_at,
                    tc.name AS category, c.name AS company,
                    CONCAT(u1.first_name,' ',u1.last_name) AS created_by,
                    CONCAT(u2.first_name,' ',u2.last_name) AS assigned_to,
                    TIMESTAMPDIFF(HOUR, t.created_at, COALESCE(t.resolved_at, NOW())) AS hours_open
             FROM tickets t
             LEFT JOIN ticket_categories tc ON tc.id = t.category_id
             LEFT JOIN companies          c  ON c.id  = t.company_id
             LEFT JOIN users              u1 ON u1.id = t.created_by
             LEFT JOIN users              u2 ON u2.id = t.assigned_to
             WHERE " . implode(' AND ', $where) . "
             ORDER BY t.created_at DESC LIMIT 1000",
            $params
        );
    }

    // ── PDF Export ────────────────────────────────────────────────

    public function exportPdf(array $filters = []): string
    {
        $summary    = $this->getSummary($filters);
        $byStatus   = $this->getByStatus($filters);
        $byPriority = $this->getByPriority($filters);
        $employees  = $this->getEmployeePerformance($filters);
        $tickets    = $this->getTicketList($filters);
        $appName    = env('APP_NAME', 'Support Portal');

        if (class_exists(\TCPDF::class)) {
            return $this->generateTcpdf($summary, $byStatus, $byPriority, $employees, $tickets, $appName);
        }
        return $this->generateHtmlReport($summary, $byStatus, $byPriority, $employees, $tickets, $appName);
    }

    private function generateTcpdf(array $summary, array $byStatus, array $byPriority, array $employees, array $tickets, string $appName): string
    {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator($appName);
        $pdf->SetTitle('Ticket Report ' . date('Y-m-d'));
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $pdf->writeHTML($this->buildReportHtml($summary, $byStatus, $byPriority, $employees, $tickets, $appName), true, false, true, false, '');
        $filename = 'report_' . date('Ymd_His') . '.pdf';
        $path     = storage_path("exports/{$filename}");
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        $pdf->Output($path, 'F');
        return $path;
    }

    private function generateHtmlReport(array $summary, array $byStatus, array $byPriority, array $employees, array $tickets, string $appName): string
    {
        $filename = 'report_' . date('Ymd_His') . '.html';
        $path     = storage_path("exports/{$filename}");
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        file_put_contents($path, $this->buildReportHtml($summary, $byStatus, $byPriority, $employees, $tickets, $appName));
        return $path;
    }

    private function buildReportHtml(array $summary, array $byStatus, array $byPriority, array $employees, array $tickets, string $appName): string
    {
        $dateRange = date('d M Y', strtotime($summary['date_from'])) . ' – ' . date('d M Y', strtotime($summary['date_to']));
        $generated = date('d M Y, H:i');

        $statusRows = '';
        foreach ($byStatus as $s) {
            $statusRows .= "<tr><td>{$s['label']}</td><td style='text-align:center;'>{$s['count']}</td></tr>";
        }
        $priorityRows = '';
        foreach ($byPriority as $p) {
            $priorityRows .= "<tr><td>{$p['label']}</td><td style='text-align:center;'>{$p['count']}</td></tr>";
        }
        $employeeRows = '';
        foreach ($employees as $e) {
            $name = htmlspecialchars(trim($e['first_name'] . ' ' . $e['last_name']));
            $avg  = $e['avg_resolution_hours'] ? round((float)$e['avg_resolution_hours'], 1) . 'h' : '–';
            $employeeRows .= "<tr><td>{$name}</td><td style='text-align:center;'>{$e['total_assigned']}</td><td style='text-align:center;'>{$e['resolved']}</td><td style='text-align:center;'>{$e['open']}</td><td style='text-align:center;'>{$avg}</td></tr>";
        }
        $ticketRows = '';
        foreach (array_slice($tickets, 0, 100) as $t) {
            $ticketRows .= '<tr>
                <td style="font-family:monospace;font-size:9px;">' . $t['ticket_number'] . '</td>
                <td>' . htmlspecialchars(mb_substr($t['subject'], 0, 45)) . '</td>
                <td>' . ucwords(str_replace('_', ' ', $t['status'])) . '</td>
                <td>' . ucfirst($t['priority']) . '</td>
                <td>' . htmlspecialchars($t['company'] ?? '–') . '</td>
                <td>' . htmlspecialchars($t['assigned_to'] ?? 'Unassigned') . '</td>
                <td>' . date('d M Y', strtotime($t['created_at'])) . '</td>
            </tr>';
        }

        return '<!DOCTYPE html><html><head><style>
body{font-family:helvetica,sans-serif;font-size:10px;color:#1e293b;}
h1{font-size:20px;color:#0d6efd;margin-bottom:2px;}
h2{font-size:13px;color:#0f172a;border-bottom:2px solid #0d6efd;padding-bottom:4px;margin-top:16px;}
p.meta{font-size:9px;color:#64748b;margin-bottom:12px;}
table{width:100%;border-collapse:collapse;margin-bottom:10px;font-size:9px;}
th{background:#0d6efd;color:#fff;padding:5px 8px;text-align:left;}
td{padding:4px 8px;border-bottom:1px solid #e2e8f0;}
tr:nth-child(even) td{background:#f8fafc;}
.box{background:#f1f5f9;border-radius:6px;padding:8px;text-align:center;display:inline-block;width:18%;margin:4px;}
.box .num{font-size:20px;font-weight:bold;color:#0d6efd;}
.box .lbl{font-size:8px;color:#64748b;}
.footer{font-size:8px;color:#94a3b8;margin-top:10px;text-align:center;}
</style></head><body>
<h1>📊 ' . $appName . ' – Ticket Report</h1>
<p class="meta">Period: ' . $dateRange . ' &nbsp;|&nbsp; Generated: ' . $generated . '</p>
<h2>Executive Summary</h2>
<div>
  <div class="box"><div class="num">' . $summary['total_tickets'] . '</div><div class="lbl">Total Tickets</div></div>
  <div class="box"><div class="num" style="color:#dc3545;">' . $summary['open_tickets'] . '</div><div class="lbl">Open</div></div>
  <div class="box"><div class="num" style="color:#198754;">' . $summary['resolved_tickets'] . '</div><div class="lbl">Resolved</div></div>
  <div class="box"><div class="num" style="color:#fd7e14;">' . $summary['avg_resolution_h'] . 'h</div><div class="lbl">Avg Resolution</div></div>
  <div class="box"><div class="num" style="color:#1e293b;">' . $summary['critical_tickets'] . '</div><div class="lbl">Critical</div></div>
</div>
<table><tr>
  <td width="50%" style="vertical-align:top;padding-right:10px;">
    <h2>By Status</h2>
    <table><tr><th>Status</th><th>Count</th></tr>' . $statusRows . '</table>
  </td>
  <td width="50%" style="vertical-align:top;">
    <h2>By Priority</h2>
    <table><tr><th>Priority</th><th>Count</th></tr>' . $priorityRows . '</table>
  </td>
</tr></table>
<h2>Employee Performance</h2>
<table><tr><th>Employee</th><th>Assigned</th><th>Resolved</th><th>Open</th><th>Avg Resolution</th></tr>' . $employeeRows . '</table>
<h2>Ticket List (First 100 of ' . $summary['total_tickets'] . ')</h2>
<table><tr><th>Ticket #</th><th>Subject</th><th>Status</th><th>Priority</th><th>Company</th><th>Assigned To</th><th>Created</th></tr>' . $ticketRows . '</table>
<p class="footer">Report generated by ' . $appName . ' on ' . $generated . '</p>
</body></html>';
    }

    // ── Excel Export ──────────────────────────────────────────────

    public function exportExcel(array $filters = []): string
    {
        $summary    = $this->getSummary($filters);
        $byStatus   = $this->getByStatus($filters);
        $byPriority = $this->getByPriority($filters);
        $employees  = $this->getEmployeePerformance($filters);
        $companies  = $this->getByCompany($filters);
        $tickets    = $this->getTicketList($filters);
        $appName    = env('APP_NAME', 'Support Portal');

        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            return $this->generateSpreadsheet($summary, $byStatus, $byPriority, $employees, $companies, $tickets, $appName);
        }
        return $this->generateCsv($tickets, $summary);
    }

    private function generateSpreadsheet(array $summary, array $byStatus, array $byPriority, array $employees, array $companies, array $tickets, string $appName): string
    {
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ss->getProperties()->setCreator($appName)->setTitle('Ticket Report ' . date('Y-m-d'));

        $blueStyle = ['font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0D6EFD']]];
        $grayStyle = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '374151']]];

        // Sheet 1: Summary
        $s1 = $ss->getActiveSheet()->setTitle('Summary');
        $dateLabel = date('d M Y', strtotime($summary['date_from'])) . ' to ' . date('d M Y', strtotime($summary['date_to']));
        $s1->setCellValue('A1', "Ticket Report – {$dateLabel}");
        $s1->mergeCells('A1:B1');
        $s1->getStyle('A1')->applyFromArray($blueStyle);
        $s1->getRowDimension(1)->setRowHeight(24);
        $summaryRows = [['Metric','Value'],['Total',$summary['total_tickets']],['Open',$summary['open_tickets']],['Resolved',$summary['resolved_tickets']],['Avg Res (h)',$summary['avg_resolution_h']],['Critical',$summary['critical_tickets']],['Period',$dateLabel],['Generated',date('d M Y H:i')]];
        foreach ($summaryRows as $i => $row) {
            $s1->setCellValue('A' . ($i + 2), $row[0]);
            $s1->setCellValue('B' . ($i + 2), $row[1]);
            if ($i === 0) $s1->getStyle('A2:B2')->applyFromArray($grayStyle);
        }
        $s1->getColumnDimension('A')->setWidth(28);
        $s1->getColumnDimension('B')->setWidth(20);
        $s1->setCellValue('D2', 'Status'); $s1->setCellValue('E2', 'Count');
        $s1->getStyle('D2:E2')->applyFromArray($grayStyle);
        $r = 3; foreach ($byStatus as $s) { $s1->setCellValue("D{$r}", $s['label']); $s1->setCellValue("E{$r}", $s['count']); $r++; }
        $s1->setCellValue('G2', 'Priority'); $s1->setCellValue('H2', 'Count');
        $s1->getStyle('G2:H2')->applyFromArray($grayStyle);
        $r = 3; foreach ($byPriority as $p) { $s1->setCellValue("G{$r}", $p['label']); $s1->setCellValue("H{$r}", $p['count']); $r++; }

        // Sheet 2: Tickets
        $s2 = $ss->createSheet()->setTitle('Tickets');
        $s2->setCellValue('A1', 'All Tickets'); $s2->mergeCells('A1:K1'); $s2->getStyle('A1')->applyFromArray($blueStyle);
        $h2 = ['Ticket #','Subject','Status','Priority','Category','Company','Created By','Assigned To','Created At','Resolved At','Hours Open'];
        foreach ($h2 as $i => $h) { $s2->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1).'2', $h); }
        $s2->getStyle('A2:K2')->applyFromArray($grayStyle);
        $r = 3;
        foreach ($tickets as $t) {
            $s2->setCellValue("A{$r}",$t['ticket_number']); $s2->setCellValue("B{$r}",$t['subject']);
            $s2->setCellValue("C{$r}",ucwords(str_replace('_',' ',$t['status']))); $s2->setCellValue("D{$r}",ucfirst($t['priority']));
            $s2->setCellValue("E{$r}",$t['category']??''); $s2->setCellValue("F{$r}",$t['company']??'');
            $s2->setCellValue("G{$r}",$t['created_by']??''); $s2->setCellValue("H{$r}",$t['assigned_to']??'Unassigned');
            $s2->setCellValue("I{$r}",$t['created_at']); $s2->setCellValue("J{$r}",$t['resolved_at']??''); $s2->setCellValue("K{$r}",$t['hours_open']??'');
            $r++;
        }
        foreach (range('A','K') as $col) { $s2->getColumnDimension($col)->setAutoSize(true); }

        // Sheet 3: Performance
        $s3 = $ss->createSheet()->setTitle('Performance');
        $s3->setCellValue('A1','Employee Performance'); $s3->mergeCells('A1:H1'); $s3->getStyle('A1')->applyFromArray($blueStyle);
        $h3 = ['Employee','Email','Assigned','Resolved','Open','In Progress','Critical','Avg Res (h)'];
        foreach ($h3 as $i => $h) { $s3->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1).'2', $h); }
        $s3->getStyle('A2:H2')->applyFromArray($grayStyle);
        $r = 3;
        foreach ($employees as $e) {
            $s3->setCellValue("A{$r}",trim($e['first_name'].' '.$e['last_name'])); $s3->setCellValue("B{$r}",$e['email']);
            $s3->setCellValue("C{$r}",$e['total_assigned']); $s3->setCellValue("D{$r}",$e['resolved']);
            $s3->setCellValue("E{$r}",$e['open']); $s3->setCellValue("F{$r}",$e['in_progress']);
            $s3->setCellValue("G{$r}",$e['critical_handled']); $s3->setCellValue("H{$r}",$e['avg_resolution_hours'] ? round((float)$e['avg_resolution_hours'],1) : '');
            $r++;
        }
        foreach (range('A','H') as $col) { $s3->getColumnDimension($col)->setAutoSize(true); }

        // Sheet 4: Companies
        $s4 = $ss->createSheet()->setTitle('Companies');
        $s4->setCellValue('A1','Company Breakdown'); $s4->mergeCells('A1:F1'); $s4->getStyle('A1')->applyFromArray($blueStyle);
        $h4 = ['Company','Total','Open','Resolved','Critical','Avg Res (h)'];
        foreach ($h4 as $i => $h) { $s4->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1).'2', $h); }
        $s4->getStyle('A2:F2')->applyFromArray($grayStyle);
        $r = 3;
        foreach ($companies as $c) {
            $s4->setCellValue("A{$r}",$c['company_name']); $s4->setCellValue("B{$r}",$c['total']);
            $s4->setCellValue("C{$r}",$c['open']); $s4->setCellValue("D{$r}",$c['resolved']);
            $s4->setCellValue("E{$r}",$c['critical']); $s4->setCellValue("F{$r}",$c['avg_resolution_hours'] ? round((float)$c['avg_resolution_hours'],1) : '');
            $r++;
        }
        foreach (range('A','F') as $col) { $s4->getColumnDimension($col)->setAutoSize(true); }

        $ss->setActiveSheetIndex(0);
        $filename = 'report_' . date('Ymd_His') . '.xlsx';
        $path     = storage_path("exports/{$filename}");
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save($path);
        return $path;
    }

    private function generateCsv(array $tickets, array $summary): string
    {
        $filename = 'report_' . date('Ymd_His') . '.csv';
        $path     = storage_path("exports/{$filename}");
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        $fp = fopen($path, 'w');
        fputcsv($fp, ['Ticket Report – Generated ' . date('d M Y H:i')]);
        fputcsv($fp, ['Period', $summary['date_from'] . ' to ' . $summary['date_to']]);
        fputcsv($fp, []);
        fputcsv($fp, ['Ticket #','Subject','Status','Priority','Category','Company','Created By','Assigned To','Created At','Resolved At','Hours Open']);
        foreach ($tickets as $t) {
            fputcsv($fp, [$t['ticket_number'],$t['subject'],ucwords(str_replace('_',' ',$t['status'])),ucfirst($t['priority']),$t['category']??'',$t['company']??'',$t['created_by']??'',$t['assigned_to']??'Unassigned',$t['created_at'],$t['resolved_at']??'',$t['hours_open']??'']);
        }
        fclose($fp);
        return $path;
    }

    private function getDateRange(array $filters): array
    {
        $preset = $filters['preset'] ?? 'last_30';
        $from   = match($preset) {
            'today'      => date('Y-m-d 00:00:00'),
            'last_7'     => date('Y-m-d 00:00:00', strtotime('-7 days')),
            'last_30'    => date('Y-m-d 00:00:00', strtotime('-30 days')),
            'last_90'    => date('Y-m-d 00:00:00', strtotime('-90 days')),
            'this_month' => date('Y-m-01 00:00:00'),
            'last_month' => date('Y-m-01 00:00:00', strtotime('first day of last month')),
            'this_year'  => date('Y-01-01 00:00:00'),
            'custom'     => (!empty($filters['date_from']) ? $filters['date_from'] . ' 00:00:00' : date('Y-m-d 00:00:00', strtotime('-30 days'))),
            default      => date('Y-m-d 00:00:00', strtotime('-30 days')),
        };
        $to = ($preset === 'custom' && !empty($filters['date_to']))
            ? $filters['date_to'] . ' 23:59:59'
            : ($preset === 'last_month'
                ? date('Y-m-t 23:59:59', strtotime('last month'))
                : date('Y-m-d 23:59:59'));
        return [$from, $to];
    }

    private function getAvgResolutionHours(string $from, string $to): float
    {
        $avg = $this->db->fetchColumn(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at))
             FROM tickets WHERE resolved_at IS NOT NULL AND created_at BETWEEN ? AND ?",
            [$from, $to]
        );
        return $avg ? round((float)$avg, 1) : 0;
    }
}
