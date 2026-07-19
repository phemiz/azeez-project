<?php
namespace App\Services;

use App\Repositories\ReportsRepository;

/**
 * Centralized SIEM Reports Service
 * Handles data mapping, compilation, and formatted output generation (CSV, Excel XML, PDF).
 */
class ReportsService {
    private ReportsRepository $repository;

    public function __construct() {
        $this->repository = new ReportsRepository();
    }

    /**
     * Resolves matching data entries and aggregates statistical totals.
     */
    public function compileReport(string $type, array $filters, string $sortBy = 'created_at', string $sortOrder = 'DESC', int $limit = 10, int $offset = 0): array {
        return $this->repository->getReportData($type, $filters, $sortBy, $sortOrder, $limit, $offset);
    }

    /**
     * Generates CSV Output stream directly.
     */
    public function generateCsv(string $type, array $data): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $type . '_report_' . date('Ymd_His') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if (!empty($data)) {
            // Write column headers
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        fclose($output);
        exit;
    }

    /**
     * Generates Excel-compatible HTML Spreadsheet stream directly.
     */
    public function generateExcel(string $type, array $data): void {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $type . '_report_' . date('Ymd_His') . '.xls');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "<table border='1'>";
        if (!empty($data)) {
            // Headers
            echo "<tr>";
            foreach (array_keys($data[0]) as $key) {
                echo "<th style='background-color:#0f172a; color:#ffffff; font-family:sans-serif;'>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";

            // Rows
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $val) {
                    echo "<td style='font-family:monospace;'>" . htmlspecialchars($val ?? '') . "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table>";
        exit;
    }

    /**
     * Generates PDF/Print view payload formats.
     */
    public function generatePdf(string $type, array $data): void {
        // Since compiling native PDF libraries offline is unstable, we output a clean print-ready 
        // HTML viewport designed for window.print() outputting PDF file copies beautifully.
        echo "<html><head><title>SIEM " . strtoupper($type) . " Report</title>";
        echo "<style>
            body { font-family: sans-serif; padding: 40px; color: black; background: white; }
            h1 { font-family: monospace; border-bottom: 2px solid black; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid black; padding: 8px; text-align: left; font-size: 12px; }
            th { background-color: #f1f5f9; }
            .meta { font-size: 10px; margin-bottom: 20px; font-family: monospace; }
        </style></head><body>";
        echo "<h1>GSM SIEM " . strtoupper($type) . " AUDIT REPORT</h1>";
        echo "<div class='meta'>Generated: " . date('Y-m-d H:i:s') . " | Records Count: " . count($data) . "</div>";
        
        echo "<table><thead><tr>";
        if (!empty($data)) {
            foreach (array_keys($data[0]) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr></thead><tbody>";
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $val) {
                    echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</tbody></table>";
        echo "<script>window.onload = function() { window.print(); }</script>";
        echo "</body></html>";
        exit;
    }
}
