<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\ReportsService;

/**
 * Enterprise Reports Dashboard Controller
 * Binds endpoints to compile, paginate, sort, filter, and export the 10 core SIEM reports.
 */
class ReportsController extends Controller {
    private ReportsService $reportsService;

    public function __construct() {
        $this->reportsService = new ReportsService();
    }

    /**
     * Renders the Central Reports SIEM Dashboard
     */
    public function index(): void {
        $user = Session::get('user');

        $reportType = $_GET['report_type'] ?? 'users';
        $search = $_GET['search'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortOrder = $_GET['sort_order'] ?? 'DESC';
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $filters = [
            'search'     => $search,
            'start_date' => $startDate,
            'end_date'   => $endDate
        ];

        // Compile filtered report data
        $result = $this->reportsService->compileReport($reportType, $filters, $sortBy, $sortOrder, $limit, $offset);

        $totalPages = ceil($result['total'] / $limit);

        // Group activity trends daily for plotting in the active report tier
        $trendData = [];
        if (!empty($result['rows'])) {
            // Count rows by date for trend rendering
            $dateCounts = [];
            foreach ($result['rows'] as $row) {
                $dt = isset($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : date('Y-m-d');
                $dateCounts[$dt] = ($dateCounts[$dt] ?? 0) + 1;
            }
            ksort($dateCounts);
            foreach ($dateCounts as $date => $count) {
                $trendData[] = ['date' => $date, 'count' => $count];
            }
        }

        $this->view('admin/reports_central', [
            'title'       => 'SIEM Centralized Reports Engine',
            'user'        => $user,
            'reportType'  => $reportType,
            'search'      => $search,
            'startDate'   => $startDate,
            'endDate'     => $endDate,
            'sortBy'      => $sortBy,
            'sortOrder'   => $sortOrder,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'totalRows'   => $result['total'],
            'rows'        => $result['rows'],
            'trendData'   => $trendData
        ]);
    }

    /**
     * Export Endpoint for PDF, Excel, and CSV downloads
     */
    public function export(): void {
        $reportType = $_GET['report_type'] ?? 'users';
        $search = $_GET['search'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortOrder = $_GET['sort_order'] ?? 'DESC';
        $format = $_GET['format'] ?? 'csv';

        $filters = [
            'search'     => $search,
            'start_date' => $startDate,
            'end_date'   => $endDate
        ];

        // Fetch up to 5000 rows for bulk exfiltration export
        $result = $this->reportsService->compileReport($reportType, $filters, $sortBy, $sortOrder, 5000, 0);

        if ($format === 'excel') {
            $this->reportsService->generateExcel($reportType, $result['rows']);
        } elseif ($format === 'pdf') {
            $this->reportsService->generatePdf($reportType, $result['rows']);
        } else {
            $this->reportsService->generateCsv($reportType, $result['rows']);
        }
    }
}
