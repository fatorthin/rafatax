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

class RekapPayroll extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.pages.rekap-payroll';

    protected static ?string $slug = 'rekap-payroll';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Rekap Payroll';

    protected static ?string $title = 'Rekap Payroll';

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
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn() => $this->exportToExcel()),
        ];
    }

    public function getPayrollData(): array
    {
        $payrolls = \App\Models\Payroll::with('details')
            ->whereYear('payroll_date', $this->year)
            ->get();

        $cols = [
            'Januari', 'Februari', 'Maret', 'THR', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        
        $monthToNameMap = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $keys = [
            'salary', 'overtime', 'visit_solo', 'visit_luar', 'bonus_lain', 'bonus_position', 'bonus_competency', 'lain2',
            'potongan_all', 'total_potongan_dan_bonus', 'gaji_dikeluarkan', 'bonus_tgl_15', 'total_gaji_dan_bonus'
        ];
        
        $matrix = [];
        foreach ($keys as $key) {
            $matrix[$key] = array_fill_keys($cols, 0);
        }

        foreach ($payrolls as $p) {
            $name = strtolower($p->name);
            if (str_contains($name, 'thr')) {
                $colKey = 'THR';
            } else {
                $month = (int) \Carbon\Carbon::parse($p->payroll_date)->format('m');
                $colKey = $monthToNameMap[$month] ?? null;
            }
            
            if (!$colKey) continue;

            foreach ($p->details as $d) {
                $bonusLembur = $d->overtime_count * $d->overtime_multiplier;
                $bonusVisitSolo = $d->visit_solo_count * 10000;
                $bonusVisitLuar = $d->visit_luar_solo_count * 15000;
                
                $cutSakit = $d->sick_leave_count * 0.5 * $d->salary / 25;
                $cutHalfday = $d->halfday_count * 0.5 * $d->salary / 25;
                $cutIjin = $d->leave_count * $d->salary / 25;
                
                $potongan = $d->cut_bpjs_kesehatan + $d->cut_bpjs_ketenagakerjaan + $d->cut_lain + $d->cut_hutang + $cutSakit + $cutHalfday + $cutIjin;
                
                $matrix['salary'][$colKey] += $d->salary;
                $matrix['overtime'][$colKey] += $bonusLembur;
                $matrix['visit_solo'][$colKey] += $bonusVisitSolo;
                $matrix['visit_luar'][$colKey] += $bonusVisitLuar;
                $matrix['bonus_lain'][$colKey] += $d->bonus_lain;
                $matrix['bonus_position'][$colKey] += $d->bonus_position;
                $matrix['bonus_competency'][$colKey] += $d->bonus_competency;
                $matrix['potongan_all'][$colKey] += $potongan;
            }
        }

        $payrollBonuses = \App\Models\PayrollBonus::with('details')
            ->whereYear('payroll_date', $this->year)
            ->get();

        foreach ($payrollBonuses as $pb) {
            $month = (int) \Carbon\Carbon::parse($pb->payroll_date)->format('m');
            $colKey = $monthToNameMap[$month] ?? null;
            if (!$colKey) continue;
            
            $matrix['bonus_tgl_15'][$colKey] += $pb->details->sum('amount');
        }

        foreach ($cols as $colKey) {
            $matrix['total_potongan_dan_bonus'][$colKey] = 
                $matrix['overtime'][$colKey] + 
                $matrix['visit_solo'][$colKey] + 
                $matrix['visit_luar'][$colKey] + 
                $matrix['bonus_lain'][$colKey] + 
                $matrix['bonus_position'][$colKey] + 
                $matrix['bonus_competency'][$colKey] + 
                $matrix['lain2'][$colKey] - 
                $matrix['potongan_all'][$colKey];
                
            $matrix['gaji_dikeluarkan'][$colKey] = $matrix['salary'][$colKey] + $matrix['total_potongan_dan_bonus'][$colKey];
            $matrix['total_gaji_dan_bonus'][$colKey] = $matrix['gaji_dikeluarkan'][$colKey] + $matrix['bonus_tgl_15'][$colKey];
        }

        return [
            'matrix' => $matrix,
            'cols' => $cols,
        ];
    }

    public function exportToExcel()
    {
        $data = $this->getPayrollData();
        $matrix = $data['matrix'];
        $cols = $data['cols'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Payroll ' . $this->year);

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $sheet->setCellValue('A1', 'REKAP PAYROLL GAJI DAN BONUS - TAHUN ' . $this->year);
        $sheet->mergeCells('A1:O1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'KETERANGAN');
        foreach ($cols as $idx => $colName) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($idx + 2) . '3', $colName);
        }
        $sheet->setCellValue('O3', 'Total / Tahun');
        $sheet->getStyle('A3:O3')->applyFromArray($headerStyle);

        $rowKeys = [
            'salary' => 'Gaji Pokok ALL',
            'overtime' => 'Lemburan',
            'visit_solo' => 'Transport Solo',
            'visit_luar' => 'Transport luar Solo',
            'bonus_lain' => 'Bonus ALL (Project)',
            'bonus_position' => 'Tunjab',
            'bonus_competency' => 'Tunkomp',
            'lain2' => 'Lain2',
            'potongan_all' => 'Potongan ALL',
            'total_potongan_dan_bonus' => 'Total Potongan dan Bonus',
            'gaji_dikeluarkan' => 'Gaji Dikeluarkan',
            'bonus_tgl_15' => 'BONUS TGL 15 / Bulan nya',
            'total_gaji_dan_bonus' => 'TOTAL GAJI DAN BONUS',
        ];

        $rowNum = 4;
        foreach ($rowKeys as $key => $label) {
            $sheet->setCellValue('A' . $rowNum, $label);
            
            $rowTotal = 0;
            foreach ($cols as $cIdx => $colName) {
                $val = $matrix[$key][$colName];
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($cIdx + 2) . $rowNum, $val);
                $rowTotal += $val;
            }
            $sheet->setCellValue('O' . $rowNum, $rowTotal);

            if (in_array($key, ['total_potongan_dan_bonus', 'gaji_dikeluarkan', 'total_gaji_dan_bonus'])) {
                $sheet->getStyle('A' . $rowNum . ':O' . $rowNum)->getFont()->setBold(true);
            }
            
            if ($key === 'total_potongan_dan_bonus') {
                $sheet->getStyle('A' . $rowNum . ':O' . $rowNum)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9C3');
            } elseif ($key === 'gaji_dikeluarkan') {
                $sheet->getStyle('A' . $rowNum . ':O' . $rowNum)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9C3');
            } elseif ($key === 'bonus_tgl_15') {
                $sheet->getStyle('A' . $rowNum . ':O' . $rowNum)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9C3');
            } elseif ($key === 'total_gaji_dan_bonus') {
                $sheet->getStyle('A' . $rowNum . ':O' . $rowNum)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DBEAFE');
            }

            $rowNum++;
        }

        $sheet->getStyle('B4:O' . ($rowNum - 1))->getNumberFormat()->setFormatCode('#,##0_);(#,##0);"-"');

        $sheet->getColumnDimension('A')->setWidth(30);
        foreach ($cols as $cIdx => $colName) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($cIdx + 2))->setWidth(15);
        }
        $sheet->getColumnDimension('O')->setWidth(20);

        $sheet->getStyle('A3:O' . ($rowNum - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'rekap-payroll-' . $this->year . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
