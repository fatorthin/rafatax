<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class RekapLaporanKeuangan extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.rekap-laporan-keuangan';

    protected static ?string $slug = 'rekap-laporan-keuangan';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Rekap Laporan Keuangan';

    protected static ?string $title = 'Rekap Laporan Keuangan';

    public int $year;

    public static function canAccess(array $parameters = []): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->hasRole('admin')) {
            return true;
        }
        return false;
    }

    public function mount(): void
    {
        $this->year = (int) request('year', now()->year);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filter Tahun')
                ->icon('heroicon-o-funnel')
                ->form([
                    Select::make('year')
                        ->label('Tahun')
                        ->options(function () {
                            $years = [];
                            $currentYear = now()->year;
                            for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
                                $years[$i] = $i;
                            }
                            return $years;
                        })
                        ->default($this->year)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->year = (int) $data['year'];
                    $this->redirect(static::getUrl(['year' => $this->year]));
                }),
            Action::make('export_laba_rugi')
                ->label('Export Laba Rugi')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn() => $this->exportLabaRugiToExcel()),
            Action::make('export_neraca')
                ->label('Export Neraca')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(fn() => $this->exportNeracaToExcel()),
        ];
    }

    public function getLabaRugiData(): array
    {
        $labaRugiService = app(\App\Services\LabaRugiReportService::class);
        
        $coas = \App\Models\Coa::where('type', 'kkp')
            ->whereIn('group_coa_id', [40, 50, 60, 70])
            ->orderBy('code')
            ->get();
            
        $matrix = [];
        foreach ($coas as $coa) {
            $matrix[$coa->code] = [
                'coa' => $coa,
                'months' => array_fill(1, 12, 0),
                'total' => 0,
            ];
        }
        
        $totals = [
            'labaKotor' => array_fill(1, 12, 0),
            'totalBiayaUsaha' => array_fill(1, 12, 0),
            'labaOperasional' => array_fill(1, 12, 0),
            'netLuarUsaha' => array_fill(1, 12, 0),
            'labaRugiTahunBerjalan' => array_fill(1, 12, 0),
            'percentLaba' => array_fill(1, 12, 0),
            'labaKotorTotal' => 0,
            'totalBiayaUsahaTotal' => 0,
            'labaOperasionalTotal' => 0,
            'netLuarUsahaTotal' => 0,
            'labaRugiTahunBerjalanTotal' => 0,
            'percentLabaTotal' => 0,
        ];
        
        for ($m = 1; $m <= 12; $m++) {
            $report = $labaRugiService->getMonthlyReport($m, $this->year);
            
            $labaKotor = 0;
            $totalBiayaUsaha = 0;
            $netLuarUsaha = 0;
            
            foreach ($report['items'] as $item) {
                $code = $item['code'];
                $amount = $item['amount'];
                $coa = $matrix[$code]['coa'] ?? null;
                if (!$coa) continue;
                
                $val = $amount;
                if ($coa->group_coa_id == 70) {
                    $val = -$amount;
                }
                
                $matrix[$code]['months'][$m] = $val;
                $matrix[$code]['total'] += $val;
                
                if ($coa->group_coa_id == 40) {
                    $labaKotor += $val;
                } elseif ($coa->group_coa_id == 50) {
                    $totalBiayaUsaha += $val;
                } elseif ($coa->group_coa_id == 60 || $coa->group_coa_id == 70) {
                    $netLuarUsaha += $val;
                }
            }
            
            $labaOperasional = $labaKotor - $totalBiayaUsaha;
            $labaRugiTahunBerjalan = $labaOperasional + $netLuarUsaha;
            $percentLaba = $labaKotor != 0 ? ($labaRugiTahunBerjalan / $labaKotor) * 100 : 0;
            
            $totals['labaKotor'][$m] = $labaKotor;
            $totals['totalBiayaUsaha'][$m] = $totalBiayaUsaha;
            $totals['labaOperasional'][$m] = $labaOperasional;
            $totals['netLuarUsaha'][$m] = $netLuarUsaha;
            $totals['labaRugiTahunBerjalan'][$m] = $labaRugiTahunBerjalan;
            $totals['percentLaba'][$m] = $percentLaba;
            
            $totals['labaKotorTotal'] += $labaKotor;
            $totals['totalBiayaUsahaTotal'] += $totalBiayaUsaha;
            $totals['labaOperasionalTotal'] += $labaOperasional;
            $totals['netLuarUsahaTotal'] += $netLuarUsaha;
            $totals['labaRugiTahunBerjalanTotal'] += $labaRugiTahunBerjalan;
        }
        
        $totals['percentLabaTotal'] = $totals['labaKotorTotal'] != 0 
            ? ($totals['labaRugiTahunBerjalanTotal'] / $totals['labaKotorTotal']) * 100 
            : 0;
            
        return [
            'matrix' => $matrix,
            'totals' => $totals,
            'coas' => $coas,
        ];
    }

    public function getNeracaData(): array
    {
        $neracaService = app(\App\Services\NeracaReportService::class);
        
        $coas = \App\Models\Coa::where('type', 'kkp')
            ->whereIn('group_coa_id', [10, 11, 12, 20, 21, 30])
            ->whereNotIn('id', [78, 118])
            ->orderBy('group_coa_id')
            ->orderBy('code')
            ->get();
            
        $matrix = [];
        foreach ($coas as $coa) {
            $matrix[$coa->code] = [
                'coa' => $coa,
                'months' => array_fill(1, 12, 0),
            ];
        }
        
        $totals = [
            'totalAktiva' => array_fill(1, 12, 0),
            'totalPasiva' => array_fill(1, 12, 0),
            'sisaDanaTahunBerjalan' => array_fill(1, 12, 0),
            'groupTotals' => [],
        ];
        
        $groupCoas = \App\Models\GroupCoa::whereIn('id', [10, 11, 12, 20, 21, 30])->get();
        foreach ($groupCoas as $group) {
            $totals['groupTotals'][$group->id] = array_fill(1, 12, 0);
        }
        
        for ($m = 1; $m <= 12; $m++) {
            $report = $neracaService->getMonthlyReport($m, $this->year);
            
            foreach ($report['aktiva'] as $item) {
                if ($item['is_group_header'] || $item['is_group_total']) continue;
                $code = $item['code'];
                $amount = $item['amount'];
                if ($item['is_negative']) $amount = -$amount;
                
                if (isset($matrix[$code])) {
                    $matrix[$code]['months'][$m] = $amount;
                    $groupId = $matrix[$code]['coa']->group_coa_id;
                    $totals['groupTotals'][$groupId][$m] += $amount;
                }
            }
            
            foreach ($report['pasiva'] as $item) {
                if ($item['is_group_header'] || $item['is_group_total']) continue;
                if ($item['is_sisa_dana']) {
                    $amount = $item['amount'];
                    if ($item['is_negative']) $amount = -$amount;
                    $totals['sisaDanaTahunBerjalan'][$m] = $amount;
                    continue;
                }
                
                $code = $item['code'];
                $amount = $item['amount'];
                if ($item['is_negative']) $amount = -$amount;
                
                if (isset($matrix[$code])) {
                    $matrix[$code]['months'][$m] = $amount;
                    $groupId = $matrix[$code]['coa']->group_coa_id;
                    $totals['groupTotals'][$groupId][$m] += $amount;
                }
            }
            
            $totals['totalAktiva'][$m] = $report['totalAktiva'];
            $totals['totalPasiva'][$m] = $report['totalPasiva'];
        }
        
        return [
            'matrix' => $matrix,
            'totals' => $totals,
            'coas' => $coas,
            'groupCoas' => $groupCoas,
        ];
    }

    public function exportLabaRugiToExcel()
    {
        $data = $this->getLabaRugiData();
        $matrix = $data['matrix'];
        $totals = $data['totals'];
        $coas = $data['coas'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Laba Rugi ' . $this->year);

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $sheet->setCellValue('A1', 'REKAP LABA RUGI KKP AO - TAHUN ' . $this->year);
        $sheet->mergeCells('A1:O1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'KETERANGAN');
        for ($m = 1; $m <= 12; $m++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . '3', $m);
        }
        $sheet->setCellValue('O3', 'TOTAL / TAHUN');
        $sheet->getStyle('A3:O3')->applyFromArray($headerStyle);

        $row = 4;

        $sheet->setCellValue('A' . $row, 'PENDAPATAN');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($coas as $coa) {
            if ($coa->group_coa_id != 40) continue;
            $code = $coa->code;
            $sheet->setCellValue('A' . $row, $coa->name);
            for ($m = 1; $m <= 12; $m++) {
                $val = $matrix[$code]['months'][$m];
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $val);
            }
            $sheet->setCellValue('O' . $row, $matrix[$code]['total']);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'LABA KOTOR');
        for ($m = 1; $m <= 12; $m++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $totals['labaKotor'][$m]);
        }
        $sheet->setCellValue('O' . $row, $totals['labaKotorTotal']);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $row++;
        $row++;

        $sheet->setCellValue('A' . $row, 'BIAYA - BIAYA');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($coas as $coa) {
            if ($coa->group_coa_id != 50) continue;
            $code = $coa->code;
            $sheet->setCellValue('A' . $row, $coa->name);
            for ($m = 1; $m <= 12; $m++) {
                $val = $matrix[$code]['months'][$m];
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $val);
            }
            $sheet->setCellValue('O' . $row, $matrix[$code]['total']);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Total Biaya Usaha');
        for ($m = 1; $m <= 12; $m++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $totals['totalBiayaUsaha'][$m]);
        }
        $sheet->setCellValue('O' . $row, $totals['totalBiayaUsahaTotal']);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $row++;

        $sheet->setCellValue('A' . $row, 'Laba Operasional');
        for ($m = 1; $m <= 12; $m++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $totals['labaOperasional'][$m]);
        }
        $sheet->setCellValue('O' . $row, $totals['labaOperasionalTotal']);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        $row++;
        $row++;

        $sheet->setCellValue('A' . $row, 'PENDAPATAN (BIAYA) LUAR USAHA');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($coas as $coa) {
            if ($coa->group_coa_id != 60 && $coa->group_coa_id != 70) continue;
            $code = $coa->code;
            $sheet->setCellValue('A' . $row, $coa->name);
            for ($m = 1; $m <= 12; $m++) {
                $val = $matrix[$code]['months'][$m];
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $val);
            }
            $sheet->setCellValue('O' . $row, $matrix[$code]['total']);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Total Pendapatan (Biaya) Luar Usaha');
        for ($m = 1; $m <= 12; $m++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $totals['netLuarUsaha'][$m]);
        }
        $sheet->setCellValue('O' . $row, $totals['netLuarUsahaTotal']);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $row++;
        $row++;

        $sheet->setCellValue('A' . $row, 'LABA(RUGI) TAHUN BERJALAN');
        for ($m = 1; $m <= 12; $m++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $totals['labaRugiTahunBerjalan'][$m]);
        }
        $sheet->setCellValue('O' . $row, $totals['labaRugiTahunBerjalanTotal']);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('CBD5E1');
        $row++;

        $sheet->setCellValue('A' . $row, '% LABA');
        for ($m = 1; $m <= 12; $m++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $totals['percentLaba'][$m] / 100);
            $sheet->getStyle(Coordinate::stringFromColumnIndex($m + 1) . $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_0);
        }
        $sheet->setCellValue('O' . $row, $totals['percentLabaTotal'] / 100);
        $sheet->getStyle('O' . $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_0);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');

        $sheet->getStyle('B4:O' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0_);(#,##0);"-"');

        $sheet->getColumnDimension('A')->setWidth(35);
        for ($m = 1; $m <= 12; $m++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($m + 1))->setWidth(15);
        }
        $sheet->getColumnDimension('O')->setWidth(20);

        $sheet->getStyle('A3:O' . $row)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'rekap-laba-rugi-' . $this->year . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportNeracaToExcel()
    {
        $data = $this->getNeracaData();
        $matrix = $data['matrix'];
        $totals = $data['totals'];
        $coas = $data['coas'];
        $groupCoas = $data['groupCoas'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Neraca ' . $this->year);

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $sheet->setCellValue('A1', 'REKAP LAPORAN NERACA - TAHUN ' . $this->year);
        $sheet->mergeCells('A1:O1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'KETERANGAN');
        for ($m = 1; $m <= 12; $m++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . '3', $m);
        }
        $sheet->setCellValue('O3', 'TOTAL / TAHUN');
        $sheet->getStyle('A3:O3')->applyFromArray($headerStyle);

        $row = 4;

        $sheet->setCellValue('A' . $row, 'AKTIVA');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;

        foreach ($groupCoas as $group) {
            if (!in_array($group->id, [10, 11, 12])) continue;
            
            $sheet->setCellValue('A' . $row, $group->name);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            
            foreach ($coas as $coa) {
                if ($coa->group_coa_id != $group->id) continue;
                $code = $coa->code;
                $sheet->setCellValue('A' . $row, $coa->name);
                $totalRowVal = 0;
                for ($m = 1; $m <= 12; $m++) {
                    $val = $matrix[$code]['months'][$m];
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $val);
                    $totalRowVal += $val;
                }
                $sheet->setCellValue('O' . $row, $totalRowVal);
                $row++;
            }
            
            $sheet->setCellValue('A' . $row, 'Total ' . $group->name);
            $totalGroupVal = 0;
            for ($m = 1; $m <= 12; $m++) {
                $val = $totals['groupTotals'][$group->id][$m];
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $val);
                $totalGroupVal += $val;
            }
            $sheet->setCellValue('O' . $row, $totalGroupVal);
            $sheet->getStyle('A' . $row . ':O' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':O' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTAL AKTIVA');
        $totalAktivaYear = 0;
        for ($m = 1; $m <= 12; $m++) {
            $val = $totals['totalAktiva'][$m];
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $val);
            $totalAktivaYear += $val;
        }
        $sheet->setCellValue('O' . $row, $totalAktivaYear);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('CBD5E1');
        $row++;
        $row++;

        $sheet->setCellValue('A' . $row, 'PASIVA');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;

        foreach ($groupCoas as $group) {
            if (!in_array($group->id, [20, 21, 30])) continue;
            
            $sheet->setCellValue('A' . $row, $group->name);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            
            foreach ($coas as $coa) {
                if ($coa->group_coa_id != $group->id) continue;
                $code = $coa->code;
                $sheet->setCellValue('A' . $row, $coa->name);
                $totalRowVal = 0;
                for ($m = 1; $m <= 12; $m++) {
                    $val = $matrix[$code]['months'][$m];
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $val);
                    $totalRowVal += $val;
                }
                $sheet->setCellValue('O' . $row, $totalRowVal);
                $row++;
            }
            
            if ($group->id == 30) {
                $sheet->setCellValue('A' . $row, 'Sisa (Lebih) Dana Tahun Berjalan');
                $totalSisaDanaYear = 0;
                for ($m = 1; $m <= 12; $m++) {
                    $val = $totals['sisaDanaTahunBerjalan'][$m];
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $val);
                    $totalSisaDanaYear += $val;
                }
                $sheet->setCellValue('O' . $row, $totalSisaDanaYear);
                $row++;
            }
            
            $sheet->setCellValue('A' . $row, 'Total ' . $group->name);
            $totalGroupVal = 0;
            for ($m = 1; $m <= 12; $m++) {
                $val = $totals['groupTotals'][$group->id][$m];
                if ($group->id == 30) {
                    $val += $totals['sisaDanaTahunBerjalan'][$m];
                }
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $val);
                $totalGroupVal += $val;
            }
            $sheet->setCellValue('O' . $row, $totalGroupVal);
            $sheet->getStyle('A' . $row . ':O' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':O' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTAL PASIVA');
        $totalPasivaYear = 0;
        for ($m = 1; $m <= 12; $m++) {
            $val = $totals['totalPasiva'][$m];
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($m + 1) . $row, $val);
            $totalPasivaYear += $val;
        }
        $sheet->setCellValue('O' . $row, $totalPasivaYear);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':O' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('CBD5E1');

        $sheet->getStyle('B4:O' . $row)->getNumberFormat()->setFormatCode('#,##0_);(#,##0);"-"');

        $sheet->getColumnDimension('A')->setWidth(35);
        for ($m = 1; $m <= 12; $m++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($m + 1))->setWidth(15);
        }
        $sheet->getColumnDimension('O')->setWidth(20);

        $sheet->getStyle('A3:O' . $row)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'rekap-neraca-' . $this->year . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
