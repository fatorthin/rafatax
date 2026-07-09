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
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
            if ($this->year === 2026) {
                $report = $this->getLabaRugiReportFor2026($m);
            } else {
                $report = $labaRugiService->getMonthlyReport($m, $this->year);
            }
            
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
            if ($this->year === 2026) {
                $report = $this->getNeracaReportFor2026($m);
            } else {
                $report = $neracaService->getMonthlyReport($m, $this->year);
            }
            
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

    private function getLabaRugiReportFor2026(int $month): array
    {
        $year = 2026;
        $startOfPreviousMonth = Carbon::create($year, $month, 1)->subMonth()->startOfMonth();
        $endOfPreviousMonth   = Carbon::create($year, $month, 1)->subMonth()->endOfMonth();
        $startOfCurrentMonth  = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfCurrentMonth    = Carbon::create($year, $month, 1)->endOfMonth();

        $startOfPreviousMonthString = $startOfPreviousMonth->toDateTimeString();
        $endOfPreviousMonthString   = $endOfPreviousMonth->toDateTimeString();
        $startOfCurrentMonthString  = $startOfCurrentMonth->toDateTimeString();
        $endOfCurrentMonthString    = $endOfCurrentMonth->toDateTimeString();

        $map = [
            188 => 119, // AO-103.6  -> AO-401   (Fee Bulanan)
            182 => 120, // AO-103.7  -> AO-401.1 (Fee SPT)
            183 => 121, // AO-103.8  -> AO-401.2 (Fee SP2DK)
            184 => 122, // AO-103.9  -> AO-401.3 (Fee Pembetulan)
            185 => 123, // AO-103.10 -> AO-401.4 (Fee Internal)
            186 => 124, // AO-103.11 -> AO-401.5 (Fee Restitusi)
            187 => 125, // AO-103.12 -> AO-401.6 (Fee Pemeriksaan)
        ];

        $rowsMou = DB::table('cost_list_mous as clm')
            ->join('mous as m', 'm.id', '=', 'clm.mou_id')
            ->whereNull('m.deleted_at')
            ->whereNull('clm.deleted_at')
            ->where('m.status', 'approved')
            ->where('m.type', 'kkp')
            ->whereBetween('m.approved_date', [$startOfCurrentMonthString, $endOfCurrentMonthString])
            ->whereIn('clm.coa_id', array_keys($map))
            ->groupBy('clm.coa_id')
            ->selectRaw('clm.coa_id, SUM(clm.total_amount) as total')
            ->get();

        $byPiutangCoa = [];
        $mouTotal     = 0;
        foreach ($rowsMou as $row) {
            $byPiutangCoa[$row->coa_id] = ($byPiutangCoa[$row->coa_id] ?? 0) + $row->total;
            $mouTotal                  += $row->total;
        }

        $rowsCash = DB::table('cash_reports')
            ->whereNull('deleted_at')
            ->whereIn('coa_id', array_keys($map))
            ->whereIn('cash_reference_id', [1, 2, 3, 4, 5, 6, 7, 9])
            ->whereBetween('transaction_date', [$startOfCurrentMonthString, $endOfCurrentMonthString])
            ->groupBy('coa_id')
            ->selectRaw('coa_id, SUM(debit_amount) as total')
            ->get();

        $byPendapatanCoa = [];
        $cashTotal       = 0;
        foreach ($rowsCash as $row) {
            $pendapatanCoaId = $map[$row->coa_id] ?? null;
            if ($pendapatanCoaId) {
                $byPendapatanCoa[$pendapatanCoaId] = ($byPendapatanCoa[$pendapatanCoaId] ?? 0) + $row->total;
                $cashTotal                        += $row->total;
            }
        }

        $coaBelumDiterimaId = 175;

        $debitCases = "CASE coa.id WHEN {$coaBelumDiterimaId} THEN {$cashTotal}";
        foreach ($byPiutangCoa as $coaId => $total) {
            $debitCases .= " WHEN {$coaId} THEN {$total}";
        }
        $debitCases .= ' ELSE 0 END';

        $kreditCases = "CASE coa.id WHEN {$coaBelumDiterimaId} THEN {$mouTotal}";
        foreach ($byPendapatanCoa as $coaId => $total) {
            $kreditCases .= " WHEN {$coaId} THEN {$total}";
        }
        $kreditCases .= ' ELSE 0 END';

        $data = \App\Models\Coa::query()
            ->select([
                'coa.id',
                'coa.code',
                'coa.name',
                'coa.type',
                'coa.group_coa_id',
                DB::raw('COALESCE(journal_data.neraca_awal_debit, 0) as neraca_awal_debit'),
                DB::raw('COALESCE(journal_data.neraca_awal_kredit, 0) as neraca_awal_kredit'),
                DB::raw('COALESCE(kas_besar_data.kas_besar_debit, 0) as kas_besar_debit'),
                DB::raw('COALESCE(kas_besar_data.kas_besar_kredit, 0) as kas_besar_kredit'),
                DB::raw('COALESCE(kas_kecil_data.kas_kecil_debit, 0) as kas_kecil_debit'),
                DB::raw('COALESCE(kas_kecil_data.kas_kecil_kredit, 0) as kas_kecil_kredit'),
                DB::raw('COALESCE(bank_data.bank_debit, 0) as bank_debit'),
                DB::raw('COALESCE(bank_data.bank_kredit, 0) as bank_kredit'),
                DB::raw("({$debitCases}) as jurnal_pendapatan_debit"),
                DB::raw("({$kreditCases}) as jurnal_pendapatan_kredit"),
                DB::raw('COALESCE(jurnal_umum_data.jurnal_umum_debit, 0) as jurnal_umum_debit'),
                DB::raw('COALESCE(jurnal_umum_data.jurnal_umum_kredit, 0) as jurnal_umum_kredit'),
                DB::raw('COALESCE(aje_data.aje_debit, 0) as aje_debit'),
                DB::raw('COALESCE(aje_data.aje_kredit, 0) as aje_kredit'),
            ])
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        SUM(debit_amount) as neraca_awal_debit,
                        SUM(credit_amount) as neraca_awal_kredit
                    FROM journal_book_reports
                    WHERE transaction_date BETWEEN '{$startOfPreviousMonthString}' AND '{$endOfPreviousMonthString}'
                    AND deleted_at IS NULL
                    AND journal_book_id = 3
                    GROUP BY coa_id
                ) as journal_data"),
                'coa.id',
                '=',
                'journal_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        debit_amount as kas_besar_kredit,
                        credit_amount as kas_besar_debit
                    FROM (
                        SELECT
                            coa_id,
                            SUM(debit_amount) as debit_amount,
                            SUM(credit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 6
                        AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                        AND deleted_at IS NULL
                        AND coa_id != 76
                        GROUP BY coa_id

                        UNION ALL

                        SELECT
                            76 as coa_id,
                            SUM(credit_amount) as debit_amount,
                            SUM(debit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 6
                        AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                        AND deleted_at IS NULL
                    ) as combined_data
                ) as kas_besar_data"),
                'coa.id',
                '=',
                'kas_besar_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        debit_amount as kas_kecil_debit,
                        credit_amount as kas_kecil_kredit
                    FROM (
                        SELECT
                            coa_id,
                            SUM(credit_amount) as debit_amount,
                            SUM(debit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 7
                        AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                        AND deleted_at IS NULL
                        AND coa_id != 77
                        GROUP BY coa_id

                        UNION ALL

                        SELECT
                            77 as coa_id,
                            SUM(debit_amount) as debit_amount,
                            SUM(credit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 7
                        AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                        AND deleted_at IS NULL
                    ) as combined_data
                ) as kas_kecil_data"),
                'coa.id',
                '=',
                'kas_kecil_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        c.id as coa_id,
                        CASE
                            WHEN c.code = 'AO-1010' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0) FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1,2,3,4,5) AND cr.deleted_at IS NULL AND cref.deleted_at IS NULL
                                AND cr.coa_id = 162 AND cr.transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                            )
                            WHEN c.code = 'AO-101.2' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0) FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1,2,3,4,5) AND cr.deleted_at IS NULL AND cref.deleted_at IS NULL
                                AND cr.coa_id IN (78) AND cr.transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                            )
                            WHEN c.code = 'AO-102.1' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=1 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.2' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=3 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.3' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=2 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.4' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=4 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.5' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=5 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            ELSE (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE coa_id=c.id AND cash_reference_id IN (1,2,3,4,5) AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                        END as bank_kredit,
                        CASE
                            WHEN c.code = 'AO-1010' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0) FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1,2,3,4,5) AND cr.deleted_at IS NULL AND cref.deleted_at IS NULL
                                AND cr.coa_id = 162 AND cr.transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                            )
                            WHEN c.code = 'AO-101.2' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0) FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1,2,3,4,5) AND cr.deleted_at IS NULL AND cref.deleted_at IS NULL
                                AND cr.coa_id IN (78) AND cr.transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                            )
                            WHEN c.code = 'AO-102.1' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=1 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.2' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=3 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.3' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=2 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.4' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=4 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.5' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=5 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            ELSE (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE coa_id=c.id AND cash_reference_id IN (1,2,3,4,5) AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                        END as bank_debit
                    FROM coa c
                ) as bank_data"),
                'coa.id',
                '=',
                'bank_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        SUM(debit_amount) as jurnal_umum_debit,
                        SUM(credit_amount) as jurnal_umum_kredit
                    FROM journal_book_reports
                    WHERE journal_book_id = 1
                    AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as jurnal_umum_data"),
                'coa.id',
                '=',
                'jurnal_umum_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        SUM(debit_amount) as aje_debit,
                        SUM(credit_amount) as aje_kredit
                    FROM journal_book_reports
                    WHERE journal_book_id = 2
                    AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as aje_data"),
                'coa.id',
                '=',
                'aje_data.coa_id'
            )
            ->whereNull('coa.deleted_at')
            ->where('coa.type', 'kkp')
            ->get();

        $items = [];
        $totalPendapatan = 0;
        $totalBeban = 0;

        $LABA_RUGI_GROUP_IDS = [40, 50, 60, 70];
        $PENDAPATAN_GROUP_IDS = [40, 60];
        $LUAR_USAHA_GROUP_IDS = [60, 70];

        foreach ($data as $row) {
            if (!in_array($row->group_coa_id, $LABA_RUGI_GROUP_IDS)) continue;

            $totalDebit = $row->neraca_awal_debit + $row->kas_besar_debit +
                $row->kas_kecil_debit + $row->bank_debit +
                $row->jurnal_umum_debit + $row->jurnal_pendapatan_debit + $row->aje_debit;

            $totalKredit = $row->neraca_awal_kredit + $row->kas_besar_kredit +
                $row->kas_kecil_kredit + $row->bank_kredit +
                $row->jurnal_umum_kredit + $row->jurnal_pendapatan_kredit + $row->aje_kredit;

            if (in_array($row->group_coa_id, $PENDAPATAN_GROUP_IDS, true)) {
                $amount = $totalKredit - $totalDebit;
                $totalPendapatan += $amount;
                $category = in_array($row->group_coa_id, $LUAR_USAHA_GROUP_IDS, true)
                    ? 'Penghasilan (Biaya) Luar Usaha'
                    : 'Pendapatan';
            } else {
                $amount = $totalDebit - $totalKredit;
                $totalBeban += $amount;
                $category = in_array($row->group_coa_id, $LUAR_USAHA_GROUP_IDS, true)
                    ? 'Penghasilan (Biaya) Luar Usaha'
                    : 'Beban';
            }

            $items[] = [
                'code' => $row->code,
                'name' => $row->name,
                'category' => $category,
                'amount' => $amount,
                'is_negative' => $amount < 0,
            ];
        }

        return [
            'items' => collect($items)->sortBy('code')->values(),
            'totalPendapatan' => abs($totalPendapatan),
            'totalBeban' => abs($totalBeban),
            'labaRugiBersih' => $totalPendapatan - $totalBeban,
        ];
    }

    private function getNeracaReportFor2026(int $month): array
    {
        $year = 2026;
        $startOfPreviousMonth = Carbon::create($year, $month, 1)->subMonth()->startOfMonth();
        $endOfPreviousMonth   = Carbon::create($year, $month, 1)->subMonth()->endOfMonth();
        $startOfCurrentMonth  = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfCurrentMonth    = Carbon::create($year, $month, 1)->endOfMonth();

        $startOfPreviousMonthString = $startOfPreviousMonth->toDateTimeString();
        $endOfPreviousMonthString   = $endOfPreviousMonth->toDateTimeString();
        $startOfCurrentMonthString  = $startOfCurrentMonth->toDateTimeString();
        $endOfCurrentMonthString    = $endOfCurrentMonth->toDateTimeString();

        $map = [
            188 => 119, // AO-103.6  -> AO-401   (Fee Bulanan)
            182 => 120, // AO-103.7  -> AO-401.1 (Fee SPT)
            183 => 121, // AO-103.8  -> AO-401.2 (Fee SP2DK)
            184 => 122, // AO-103.9  -> AO-401.3 (Fee Pembetulan)
            185 => 123, // AO-103.10 -> AO-401.4 (Fee Internal)
            186 => 124, // AO-103.11 -> AO-401.5 (Fee Restitusi)
            187 => 125, // AO-103.12 -> AO-401.6 (Fee Pemeriksaan)
        ];

        $rowsMou = DB::table('cost_list_mous as clm')
            ->join('mous as m', 'm.id', '=', 'clm.mou_id')
            ->whereNull('m.deleted_at')
            ->whereNull('clm.deleted_at')
            ->where('m.status', 'approved')
            ->where('m.type', 'kkp')
            ->whereBetween('m.approved_date', [$startOfCurrentMonthString, $endOfCurrentMonthString])
            ->whereIn('clm.coa_id', array_keys($map))
            ->groupBy('clm.coa_id')
            ->selectRaw('clm.coa_id, SUM(clm.total_amount) as total')
            ->get();

        $byPiutangCoa = [];
        $mouTotal     = 0;
        foreach ($rowsMou as $row) {
            $byPiutangCoa[$row->coa_id] = ($byPiutangCoa[$row->coa_id] ?? 0) + $row->total;
            $mouTotal                  += $row->total;
        }

        $rowsCash = DB::table('cash_reports')
            ->whereNull('deleted_at')
            ->whereIn('coa_id', array_keys($map))
            ->whereIn('cash_reference_id', [1, 2, 3, 4, 5, 6, 7, 9])
            ->whereBetween('transaction_date', [$startOfCurrentMonthString, $endOfCurrentMonthString])
            ->groupBy('coa_id')
            ->selectRaw('coa_id, SUM(debit_amount) as total')
            ->get();

        $byPendapatanCoa = [];
        $cashTotal       = 0;
        foreach ($rowsCash as $row) {
            $pendapatanCoaId = $map[$row->coa_id] ?? null;
            if ($pendapatanCoaId) {
                $byPendapatanCoa[$pendapatanCoaId] = ($byPendapatanCoa[$pendapatanCoaId] ?? 0) + $row->total;
                $cashTotal                        += $row->total;
            }
        }

        $coaBelumDiterimaId = 175;

        $debitCases = "CASE coa.id WHEN {$coaBelumDiterimaId} THEN {$cashTotal}";
        foreach ($byPiutangCoa as $coaId => $total) {
            $debitCases .= " WHEN {$coaId} THEN {$total}";
        }
        $debitCases .= ' ELSE 0 END';

        $kreditCases = "CASE coa.id WHEN {$coaBelumDiterimaId} THEN {$mouTotal}";
        foreach ($byPendapatanCoa as $coaId => $total) {
            $kreditCases .= " WHEN {$coaId} THEN {$total}";
        }
        $kreditCases .= ' ELSE 0 END';

        $labaRugiReport = $this->getLabaRugiReportFor2026($month);
        $sisaDanaTahunBerjalan = $labaRugiReport['labaRugiBersih'];

        $data = \App\Models\Coa::query()
            ->select([
                'coa.id',
                'coa.code',
                'coa.name',
                'coa.type',
                'coa.group_coa_id',
                'group_coas.name as group_name',
                DB::raw('COALESCE(journal_data.neraca_awal_debit, 0) as neraca_awal_debit'),
                DB::raw('COALESCE(journal_data.neraca_awal_kredit, 0) as neraca_awal_kredit'),
                DB::raw('COALESCE(kas_besar_data.kas_besar_debit, 0) as kas_besar_debit'),
                DB::raw('COALESCE(kas_besar_data.kas_besar_kredit, 0) as kas_besar_kredit'),
                DB::raw('COALESCE(kas_kecil_data.kas_kecil_debit, 0) as kas_kecil_debit'),
                DB::raw('COALESCE(kas_kecil_data.kas_kecil_kredit, 0) as kas_kecil_kredit'),
                DB::raw('COALESCE(bank_data.bank_debit, 0) as bank_debit'),
                DB::raw('COALESCE(bank_data.bank_kredit, 0) as bank_kredit'),
                DB::raw("({$debitCases}) as jurnal_pendapatan_debit"),
                DB::raw("({$kreditCases}) as jurnal_pendapatan_kredit"),
                DB::raw('COALESCE(jurnal_umum_data.jurnal_umum_debit, 0) as jurnal_umum_debit'),
                DB::raw('COALESCE(jurnal_umum_data.jurnal_umum_kredit, 0) as jurnal_umum_kredit'),
                DB::raw('COALESCE(aje_data.aje_debit, 0) as aje_debit'),
                DB::raw('COALESCE(aje_data.aje_kredit, 0) as aje_kredit'),
            ])
            ->leftJoin('group_coas', 'coa.group_coa_id', '=', 'group_coas.id')
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        SUM(debit_amount) as neraca_awal_debit,
                        SUM(credit_amount) as neraca_awal_kredit
                    FROM journal_book_reports
                    WHERE transaction_date BETWEEN '{$startOfPreviousMonthString}' AND '{$endOfPreviousMonthString}'
                    AND deleted_at IS NULL
                    AND journal_book_id = 3
                    GROUP BY coa_id
                ) as journal_data"),
                'coa.id',
                '=',
                'journal_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        debit_amount as kas_besar_kredit,
                        credit_amount as kas_besar_debit
                    FROM (
                        SELECT
                            coa_id,
                            SUM(debit_amount) as debit_amount,
                            SUM(credit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 6
                        AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                        AND deleted_at IS NULL
                        AND coa_id != 76
                        GROUP BY coa_id

                        UNION ALL

                        SELECT
                            76 as coa_id,
                            SUM(credit_amount) as debit_amount,
                            SUM(debit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 6
                        AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                        AND deleted_at IS NULL
                    ) as combined_data
                ) as kas_besar_data"),
                'coa.id',
                '=',
                'kas_besar_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        debit_amount as kas_kecil_debit,
                        credit_amount as kas_kecil_kredit
                    FROM (
                        SELECT
                            coa_id,
                            SUM(credit_amount) as debit_amount,
                            SUM(debit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 7
                        AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                        AND deleted_at IS NULL
                        AND coa_id != 77
                        GROUP BY coa_id

                        UNION ALL

                        SELECT
                            77 as coa_id,
                            SUM(debit_amount) as debit_amount,
                            SUM(credit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 7
                        AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                        AND deleted_at IS NULL
                    ) as combined_data
                ) as kas_kecil_data"),
                'coa.id',
                '=',
                'kas_kecil_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        c.id as coa_id,
                        CASE
                            WHEN c.code = 'AO-1010' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0) FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1,2,3,4,5) AND cr.deleted_at IS NULL AND cref.deleted_at IS NULL
                                AND cr.coa_id = 162 AND cr.transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                            )
                            WHEN c.code = 'AO-101.2' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0) FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1,2,3,4,5) AND cr.deleted_at IS NULL AND cref.deleted_at IS NULL
                                AND cr.coa_id IN (78) AND cr.transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                            )
                            WHEN c.code = 'AO-102.1' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=1 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.2' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=3 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.3' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=2 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.4' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=4 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.5' THEN (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE cash_reference_id=5 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            ELSE (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE coa_id=c.id AND cash_reference_id IN (1,2,3,4,5) AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                        END as bank_kredit,
                        CASE
                            WHEN c.code = 'AO-1010' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0) FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1,2,3,4,5) AND cr.deleted_at IS NULL AND cref.deleted_at IS NULL
                                AND cr.coa_id = 162 AND cr.transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                            )
                            WHEN c.code = 'AO-101.2' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0) FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1,2,3,4,5) AND cr.deleted_at IS NULL AND cref.deleted_at IS NULL
                                AND cr.coa_id IN (78) AND cr.transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                            )
                            WHEN c.code = 'AO-102.1' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=1 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.2' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=3 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.3' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=2 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.4' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=4 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            WHEN c.code = 'AO-102.5' THEN (SELECT COALESCE(SUM(debit_amount),0) FROM cash_reports WHERE cash_reference_id=5 AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                            ELSE (SELECT COALESCE(SUM(credit_amount),0) FROM cash_reports WHERE coa_id=c.id AND cash_reference_id IN (1,2,3,4,5) AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}' AND deleted_at IS NULL)
                        END as bank_debit
                    FROM coa c
                ) as bank_data"),
                'coa.id',
                '=',
                'bank_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        SUM(debit_amount) as jurnal_umum_debit,
                        SUM(credit_amount) as jurnal_umum_kredit
                    FROM journal_book_reports
                    WHERE journal_book_id = 1
                    AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as jurnal_umum_data"),
                'coa.id',
                '=',
                'jurnal_umum_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT
                        coa_id,
                        SUM(debit_amount) as aje_debit,
                        SUM(credit_amount) as aje_kredit
                    FROM journal_book_reports
                    WHERE journal_book_id = 2
                    AND transaction_date BETWEEN '{$startOfCurrentMonthString}' AND '{$endOfCurrentMonthString}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as aje_data"),
                'coa.id',
                '=',
                'aje_data.coa_id'
            )
            ->whereNull('coa.deleted_at')
            ->where('coa.type', 'kkp')
            ->whereNotIn('coa.id', [78, 118])
            ->whereIn('coa.group_coa_id', [10, 11, 12, 20, 21, 30])
            ->orderBy('group_coas.id')
            ->orderBy('coa.code')
            ->get();

        // Specific sum calculation for AO-103 for the 2026 reporting data source
        $targetCodes = ['AO-103.5', 'AO-103.6', 'AO-103.7', 'AO-103.8', 'AO-103.9', 'AO-103.10', 'AO-103.11', 'AO-103.12'];
        $subRows = $data->filter(fn($row) => in_array($row->code, $targetCodes));
        $mainRow = $data->first(fn($row) => $row->code === 'AO-103');
        if ($mainRow && $subRows->isNotEmpty()) {
            $columnsToSum = [
                'kas_besar_debit', 'kas_besar_kredit',
                'kas_kecil_debit', 'kas_kecil_kredit',
                'bank_debit', 'bank_kredit',
                'jurnal_pendapatan_debit', 'jurnal_pendapatan_kredit',
                'jurnal_umum_debit', 'jurnal_umum_kredit',
                'aje_debit', 'aje_kredit'
            ];
            foreach ($columnsToSum as $col) {
                $mainRow->$col = $subRows->sum($col);
            }
        }

        $neracaData = [
            'aktiva' => [],
            'pasiva' => [],
            'totalAktiva' => 0,
            'totalPasiva' => 0,
        ];

        $currentGroup = null;
        $currentGroupName = '';
        $currentGroupTotal = 0;
        $currentGroupSide = null;

        $defaultItemStructure = [
            'code' => '',
            'name' => '',
            'amount' => 0,
            'is_negative' => false,
            'is_group_header' => false,
            'is_group_total' => false,
            'is_sisa_dana' => false,
        ];

        $AKTIVA_GROUP_IDS = [10, 11, 12];

        foreach ($data as $row) {
            $totalDebit = $row->neraca_awal_debit + $row->kas_besar_debit +
                $row->kas_kecil_debit + $row->bank_debit +
                $row->jurnal_umum_debit + $row->jurnal_pendapatan_debit + $row->aje_debit;

            $totalKredit = $row->neraca_awal_kredit + $row->kas_besar_kredit +
                $row->kas_kecil_kredit + $row->bank_kredit +
                $row->jurnal_umum_kredit + $row->jurnal_pendapatan_kredit + $row->aje_kredit;

            $isAktiva = in_array($row->group_coa_id, $AKTIVA_GROUP_IDS, true);
            $amount = $isAktiva
                ? $totalDebit - $totalKredit
                : $totalKredit - $totalDebit;

            $target = $isAktiva ? 'aktiva' : 'pasiva';

            if ($currentGroup !== $row->group_coa_id) {
                if ($currentGroup !== null) {
                    $previousTarget = $currentGroupSide ?? $target;
                    $neracaData[$previousTarget][] = array_merge($defaultItemStructure, [
                        'name' => 'Total ' . $currentGroupName,
                        'amount' => abs($currentGroupTotal),
                        'is_negative' => $currentGroupTotal < 0,
                        'is_group_total' => true,
                    ]);
                }

                $currentGroup = $row->group_coa_id;
                $currentGroupName = $row->group_name ?? 'Lainnya';
                $currentGroupTotal = 0;
                $currentGroupSide = $target;

                $neracaData[$target][] = array_merge($defaultItemStructure, [
                    'name' => $currentGroupName,
                    'is_group_header' => true,
                ]);
            }

            $neracaData[$target][] = array_merge($defaultItemStructure, [
                'code' => $row->code,
                'name' => $row->name,
                'amount' => abs($amount),
                'is_negative' => $amount < 0,
            ]);

            $currentGroupTotal += $amount;
            if ($isAktiva) {
                $neracaData['totalAktiva'] += $amount;
            } else {
                $neracaData['totalPasiva'] += $amount;
            }
        }

        if ($currentGroup !== null && $currentGroupSide !== null) {
            $neracaData[$currentGroupSide][] = array_merge($defaultItemStructure, [
                'name' => 'Total ' . $currentGroupName,
                'amount' => abs($currentGroupTotal),
                'is_negative' => $currentGroupTotal < 0,
                'is_group_total' => true,
            ]);
        }

        $neracaData['pasiva'][] = array_merge($defaultItemStructure, [
            'name' => 'Sisa (Lebih) Dana Tahun Berjalan',
            'amount' => abs($sisaDanaTahunBerjalan),
            'is_negative' => $sisaDanaTahunBerjalan < 0,
            'is_sisa_dana' => true,
        ]);
        $neracaData['totalPasiva'] += $sisaDanaTahunBerjalan;

        return $neracaData;
    }
}
