<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AuditService;

/**
 * Enterprise Compliance Audit Trail Controller
 * Manages administrative logs search query bounds, date filtering, and CSV download streams.
 */
class AuditController extends Controller {
    private AuditService $auditService;

    public function __construct() {
        $this->auditService = new AuditService();
    }

    /**
     * Renders the administrative audit ledger workspace
     */
    public function index(): void {
        $user = Session::get('user');

        $search = $_GET['search'] ?? '';
        $actionType = $_GET['action_type'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortOrder = $_GET['sort_order'] ?? 'DESC';

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $filters = [
            'search'      => $search,
            'action_type' => $actionType,
            'start_date'  => $startDate,
            'end_date'    => $endDate
        ];

        $result = $this->auditService->getAudits($filters, $sortBy, $sortOrder, $limit, $offset);
        $totalPages = ceil($result['total'] / $limit);

        // Group daily changes for chart visualizer
        $chartData = [];
        if (!empty($result['rows'])) {
            $counts = [];
            foreach ($result['rows'] as $r) {
                $dt = date('Y-m-d', strtotime($r['created_at']));
                $counts[$dt] = ($counts[$dt] ?? 0) + 1;
            }
            ksort($counts);
            foreach ($counts as $date => $count) {
                $chartData[] = ['date' => $date, 'count' => $count];
            }
        }

        $this->view('admin/audit', [
            'title'       => 'Enterprise Compliance Audit Trail',
            'user'        => $user,
            'search'      => $search,
            'actionType'  => $actionType,
            'startDate'   => $startDate,
            'endDate'     => $endDate,
            'sortBy'      => $sortBy,
            'sortOrder'   => $sortOrder,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'totalRows'   => $result['total'],
            'rows'        => $result['rows'],
            'chartData'   => $chartData
        ]);
    }

    /**
     * Streams compliance audit log file directly
     */
    public function export(): void {
        $search = $_GET['search'] ?? '';
        $actionType = $_GET['action_type'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortOrder = $_GET['sort_order'] ?? 'DESC';

        $filters = [
            'search'      => $search,
            'action_type' => $actionType,
            'start_date'  => $startDate,
            'end_date'    => $endDate
        ];

        // Fetch up to 5000 rows
        $result = $this->auditService->getAudits($filters, $sortBy, $sortOrder, 5000, 0);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=compliance_audit_' . date('Ymd_His') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Table Name', 'Record ID', 'Action Type', 'Old Values', 'New Values', 'Operator ID', 'IP Address', 'User Agent', 'Session ID', 'Timestamp']);

        foreach ($result['rows'] as $row) {
            fputcsv($output, [
                $row['id'],
                $row['table_name'],
                $row['record_id'],
                $row['action_type'],
                $row['old_values'],
                $row['new_values'],
                $row['performed_by'],
                $row['ip_address'],
                $row['user_agent'],
                $row['session_id'],
                $row['created_at']
            ]);
        }

        fclose($output);
        exit;
    }
}
