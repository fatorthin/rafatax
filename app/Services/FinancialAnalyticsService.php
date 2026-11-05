<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialAnalyticsService
{
    public function __construct(
        protected LabaRugiReportService $labaRugiService
    ) {}

    public static function getMonthNames(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    public static function getMonthShortNames(): array
    {
        return [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ags',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];
    }

    /**
     * Get monthly summary and item details
     */
    public function getMonthlyData(int $month, int $year, string $entity = 'all'): array
    {
        $report = $this->labaRugiService->getMonthlyReport($month, $year, $entity);

        $totalPendapatan = (float) ($report['totalPendapatan'] ?? 0);
        $totalBeban = (float) ($report['totalBeban'] ?? 0);
        $labaRugiBersih = (float) ($report['labaRugiBersih'] ?? 0);

        $margin = $totalPendapatan > 0
            ? ($labaRugiBersih / $totalPendapatan) * 100
            : 0;

        $items = collect($report['items'] ?? []);

        // Top Expenses Breakdown for Doughnut Chart
        $expenseItems = $items->filter(fn($i) => ($i['category'] ?? '') === 'Beban' && (float)$i['amount'] > 0)
            ->sortByDesc('amount')
            ->values();

        $topExpenses = $expenseItems->take(5);
        $otherExpenseSum = $expenseItems->slice(5)->sum('amount');

        $expenseBreakdown = [];
        foreach ($topExpenses as $item) {
            $expenseBreakdown[] = [
                'label' => $item['name'],
                'amount' => (float) $item['amount'],
            ];
        }

        if ($otherExpenseSum > 0) {
            $expenseBreakdown[] = [
                'label' => 'Beban Lainnya',
                'amount' => (float) $otherExpenseSum,
            ];
        }

        return [
            'month' => $month,
            'year' => $year,
            'period_label' => self::getMonthNames()[$month] . ' ' . $year,
            'entity' => $entity,
            'totalPendapatan' => $totalPendapatan,
            'totalBeban' => $totalBeban,
            'labaRugiBersih' => $labaRugiBersih,
            'netProfitMargin' => $margin,
            'items' => $items,
            'expenseBreakdown' => $expenseBreakdown,
        ];
    }

    /**
     * Compare two monthly periods (e.g. Month 1 vs Month 2)
     */
    public function getComparison(
        int $primaryMonth,
        int $primaryYear,
        int $compareMonth,
        int $compareYear,
        string $entity = 'all'
    ): array {
        $p1 = $this->getMonthlyData($primaryMonth, $primaryYear, $entity);
        $p2 = $this->getMonthlyData($compareMonth, $compareYear, $entity);

        // KPI Variances
        $diffPendapatan = $p1['totalPendapatan'] - $p2['totalPendapatan'];
        $pctPendapatan = $p2['totalPendapatan'] != 0
            ? ($diffPendapatan / abs($p2['totalPendapatan'])) * 100
            : ($p1['totalPendapatan'] > 0 ? 100 : 0);

        $diffBeban = $p1['totalBeban'] - $p2['totalBeban'];
        $pctBeban = $p2['totalBeban'] != 0
            ? ($diffBeban / abs($p2['totalBeban'])) * 100
            : ($p1['totalBeban'] > 0 ? 100 : 0);

        $diffLaba = $p1['labaRugiBersih'] - $p2['labaRugiBersih'];
        $pctLaba = $p2['labaRugiBersih'] != 0
            ? ($diffLaba / abs($p2['labaRugiBersih'])) * 100
            : ($p1['labaRugiBersih'] > 0 ? 100 : 0);

        $diffMargin = $p1['netProfitMargin'] - $p2['netProfitMargin'];

        // Merge Items Comparison per COA Code
        $items1 = $p1['items']->keyBy('code');
        $items2 = $p2['items']->keyBy('code');

        $allCodes = $items1->keys()->merge($items2->keys())->unique()->sort()->values();

        $mergedItems = [];
        $categories = ['Pendapatan', 'Beban', 'Penghasilan (Biaya) Luar Usaha'];

        foreach ($allCodes as $code) {
            $item1 = $items1->get($code);
            $item2 = $items2->get($code);

            $name = $item1['name'] ?? $item2['name'] ?? '-';
            $category = $item1['category'] ?? $item2['category'] ?? 'Beban';

            $val1 = (float) ($item1['amount'] ?? 0);
            $val2 = (float) ($item2['amount'] ?? 0);

            // Skip accounts with zero in both periods
            if ($val1 == 0 && $val2 == 0) {
                continue;
            }

            $diffNominal = $val1 - $val2;
            $diffPercent = $val2 != 0
                ? ($diffNominal / abs($val2)) * 100
                : ($val1 != 0 ? 100 : 0);

            // Favorable logic:
            // For revenue: growth (+) is favorable
            // For expense: decrease (-) is favorable
            if ($category === 'Pendapatan') {
                $isFavorable = $diffNominal >= 0;
            } elseif ($category === 'Beban') {
                $isFavorable = $diffNominal <= 0;
            } else {
                $isFavorable = $diffNominal >= 0;
            }

            $mergedItems[] = [
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'val1' => $val1,
                'val2' => $val2,
                'diff_nominal' => $diffNominal,
                'diff_percent' => $diffPercent,
                'is_favorable' => $isFavorable,
            ];
        }

        $itemsGrouped = collect($mergedItems)->groupBy('category');

        return [
            'period1' => $p1,
            'period2' => $p2,
            'kpi' => [
                'pendapatan' => [
                    'current' => $p1['totalPendapatan'],
                    'previous' => $p2['totalPendapatan'],
                    'diff_nominal' => $diffPendapatan,
                    'diff_percent' => $pctPendapatan,
                    'is_favorable' => $diffPendapatan >= 0,
                ],
                'beban' => [
                    'current' => $p1['totalBeban'],
                    'previous' => $p2['totalBeban'],
                    'diff_nominal' => $diffBeban,
                    'diff_percent' => $pctBeban,
                    'is_favorable' => $diffBeban <= 0, // cost reduction is favorable
                ],
                'laba_bersih' => [
                    'current' => $p1['labaRugiBersih'],
                    'previous' => $p2['labaRugiBersih'],
                    'diff_nominal' => $diffLaba,
                    'diff_percent' => $pctLaba,
                    'is_favorable' => $diffLaba >= 0,
                ],
                'margin' => [
                    'current' => $p1['netProfitMargin'],
                    'previous' => $p2['netProfitMargin'],
                    'diff_margin' => $diffMargin,
                    'is_favorable' => $diffMargin >= 0,
                ],
            ],
            'items_grouped' => $itemsGrouped,
            'categories' => $categories,
        ];
    }

    /**
     * Get 12-Month trend data for charts
     */
    public function get12MonthTrend(int $year, string $entity = 'all'): array
    {
        $months = self::getMonthShortNames();
        $labels = [];
        $pendapatanData = [];
        $bebanData = [];
        $labaData = [];
        $marginData = [];

        foreach ($months as $m => $name) {
            $labels[] = $name;
            $data = $this->getMonthlyData($m, $year, $entity);

            $pendapatanData[] = round($data['totalPendapatan'], 2);
            $bebanData[] = round($data['totalBeban'], 2);
            $labaData[] = round($data['labaRugiBersih'], 2);
            $marginData[] = round($data['netProfitMargin'], 1);
        }

        return [
            'year' => $year,
            'entity' => $entity,
            'labels' => $labels,
            'pendapatan' => $pendapatanData,
            'beban' => $bebanData,
            'laba' => $labaData,
            'margin' => $marginData,
        ];
    }

    /**
     * Export comparison to Excel spreadsheet
     */
    public function exportComparisonToExcel(
        int $p1Month,
        int $p1Year,
        int $p2Month,
        int $p2Year,
        string $entity = 'all'
    ): StreamedResponse {
        $comparison = $this->getComparison($p1Month, $p1Year, $p2Month, $p2Year, $entity);
        $p1 = $comparison['period1'];
        $p2 = $comparison['period2'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Analisis Komparasi Bulanan');

        // Page Title & Header
        $sheet->setCellValue('A1', 'ANALISIS KOMPARASI LAPORAN KEUANGAN BULANAN');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $entityLabel = match ($entity) {
            'pt' => 'ENTITAS: PT',
            'kkp' => 'ENTITAS: KKP',
            default => 'KONSOLIDASI (SEMUA ENTITAS)',
        };
        $sheet->setCellValue('A2', $entityLabel . ' | Periode: ' . $p1['period_label'] . ' vs ' . $p2['period_label']);
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setSize(11)->setItalic(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // KPI Summary Box
        $sheet->setCellValue('A4', 'RINGKASAN EKSEKUTIF');
        $sheet->mergeCells('A4:G4');
        $sheet->getStyle('A4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B');

        $headersKpi = ['Metrik', $p1['period_label'], $p2['period_label'], 'Selisih (Rp)', 'Pertumbuhan (%)', 'Status Evaluasi'];
        $colIndex = 'A';
        foreach ($headersKpi as $h) {
            $sheet->setCellValue($colIndex . '5', $h);
            $sheet->getStyle($colIndex . '5')->getFont()->setBold(true);
            $sheet->getStyle($colIndex . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
            $colIndex++;
        }

        $kpis = [
            [
                'Total Pendapatan',
                $comparison['kpi']['pendapatan']['current'],
                $comparison['kpi']['pendapatan']['previous'],
                $comparison['kpi']['pendapatan']['diff_nominal'],
                $comparison['kpi']['pendapatan']['diff_percent'],
                $comparison['kpi']['pendapatan']['is_favorable'] ? 'Meningkat' : 'Menurun',
            ],
            [
                'Total Beban Operasional',
                $comparison['kpi']['beban']['current'],
                $comparison['kpi']['beban']['previous'],
                $comparison['kpi']['beban']['diff_nominal'],
                $comparison['kpi']['beban']['diff_percent'],
                $comparison['kpi']['beban']['is_favorable'] ? 'Efisien' : 'Membengkak',
            ],
            [
                'Laba / (Rugi) Bersih',
                $comparison['kpi']['laba_bersih']['current'],
                $comparison['kpi']['laba_bersih']['previous'],
                $comparison['kpi']['laba_bersih']['diff_nominal'],
                $comparison['kpi']['laba_bersih']['diff_percent'],
                $comparison['kpi']['laba_bersih']['is_favorable'] ? 'Favorable' : 'Unfavorable',
            ],
            [
                'Net Profit Margin (%)',
                number_format($comparison['kpi']['margin']['current'], 2) . '%',
                number_format($comparison['kpi']['margin']['previous'], 2) . '%',
                number_format($comparison['kpi']['margin']['diff_margin'], 2) . ' %pt',
                '-',
                $comparison['kpi']['margin']['is_favorable'] ? 'Ekspansi' : 'Kompresi',
            ],
        ];

        $rowKpi = 6;
        foreach ($kpis as $row) {
            $sheet->setCellValue('A' . $rowKpi, $row[0]);
            $sheet->setCellValue('B' . $rowKpi, is_numeric($row[1]) ? $row[1] : $row[1]);
            $sheet->setCellValue('C' . $rowKpi, is_numeric($row[2]) ? $row[2] : $row[2]);
            $sheet->setCellValue('D' . $rowKpi, is_numeric($row[3]) ? $row[3] : $row[3]);
            $sheet->setCellValue('E' . $rowKpi, is_numeric($row[4]) ? number_format($row[4], 2) . '%' : $row[4]);
            $sheet->setCellValue('F' . $rowKpi, $row[5]);

            if (is_numeric($row[1])) {
                $sheet->getStyle('B' . $rowKpi . ':D' . $rowKpi)->getNumberFormat()->setFormatCode('#,##0');
            }
            $rowKpi++;
        }

        // Table Header
        $rowTable = $rowKpi + 2;
        $sheet->setCellValue('A' . $rowTable, 'RINCIAN PER AKUN COA (VARIANCE ANALYSIS)');
        $sheet->mergeCells('A' . $rowTable . ':G' . $rowTable);
        $sheet->getStyle('A' . $rowTable)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A' . $rowTable)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('334155');

        $rowTable++;
        $headersTable = ['Kode Akun', 'Nama Akun', 'Kategori', $p1['period_label'], $p2['period_label'], 'Selisih (Rp)', 'Pertumbuhan (%)'];
        $c = 'A';
        foreach ($headersTable as $th) {
            $sheet->setCellValue($c . $rowTable, $th);
            $sheet->getStyle($c . $rowTable)->getFont()->setBold(true);
            $sheet->getStyle($c . $rowTable)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CBD5E1');
            $c++;
        }

        $rowCurr = $rowTable + 1;
        foreach ($comparison['categories'] as $cat) {
            $catItems = $comparison['items_grouped']->get($cat, collect());
            if ($catItems->isEmpty()) {
                continue;
            }

            // Category Subheader
            $sheet->setCellValue('A' . $rowCurr, strtoupper($cat));
            $sheet->mergeCells('A' . $rowCurr . ':G' . $rowCurr);
            $sheet->getStyle('A' . $rowCurr)->getFont()->setBold(true);
            $sheet->getStyle('A' . $rowCurr)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
            $rowCurr++;

            foreach ($catItems as $item) {
                $sheet->setCellValue('A' . $rowCurr, $item['code']);
                $sheet->setCellValue('B' . $rowCurr, $item['name']);
                $sheet->setCellValue('C' . $rowCurr, $item['category']);
                $sheet->setCellValue('D' . $rowCurr, $item['val1']);
                $sheet->setCellValue('E' . $rowCurr, $item['val2']);
                $sheet->setCellValue('F' . $rowCurr, $item['diff_nominal']);
                $sheet->setCellValue('G' . $rowCurr, number_format($item['diff_percent'], 2) . '%');

                $sheet->getStyle('D' . $rowCurr . ':F' . $rowCurr)->getNumberFormat()->setFormatCode('#,##0');

                // Color indicator for growth
                if ($item['diff_nominal'] != 0) {
                    $color = $item['is_favorable'] ? '15803D' : 'B91C1C';
                    $sheet->getStyle('G' . $rowCurr)->getFont()->getColor()->setRGB($color);
                }

                $rowCurr++;
            }
        }

        // Auto width for columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Analisis_Keuangan_' . $p1Month . '_' . $p1Year . '_vs_' . $p2Month . '_' . $p2Year . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}

