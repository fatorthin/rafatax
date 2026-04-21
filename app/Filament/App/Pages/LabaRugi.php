<?php

namespace App\Filament\App\Pages;

use App\Services\LabaRugiReportService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LabaRugi extends Page
{
    protected static string $view = 'filament.app.pages.laba-rugi';
    protected static ?string $title = 'Laporan Laba Rugi';

    public $month;
    public $year;

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
        return $user->hasPermission('cash-report.view') || $user->hasPermission('cash-reports.view');
    }

    public function mount(): void
    {
        $this->month = request('month', now()->month);
        $this->year = request('year', now()->year);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filter Periode')
                ->icon('heroicon-o-funnel')
                ->form([
                    Select::make('month')
                        ->label('Bulan')
                        ->options([
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
                            12 => 'Desember'
                        ])
                        ->default($this->month)
                        ->required(),
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
                    $this->month = $data['month'];
                    $this->year = $data['year'];
                    $this->redirect(route('filament.app.pages.laba-rugi', ['month' => $this->month, 'year' => $this->year]));
                }),
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn() => $this->exportToExcel()),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('secondary')
                ->url(fn() => route('filament.app.pages.neraca-lajur-bulanan', ['month' => $this->month, 'year' => $this->year])),
        ];
    }

    public function getLabaRugiData()
    {
        return app(LabaRugiReportService::class)->getMonthlyReport((int) $this->month, (int) $this->year);
    }

    public function exportToExcel()
    {
        $monthNames = [
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
            12 => 'Desember'
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $title = 'LAPORAN LABA RUGI - ' . strtoupper($monthNames[$this->month]) . ' ' . $this->year;

        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $labaRugiData = $this->getLabaRugiData();

        $sheet->mergeCells('A3:C3');
        $sheet->setCellValue('A3', 'Pendapatan');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3:C3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');

        $row = 4;
        $totalPendapatanDisplay = 0;
        foreach ($labaRugiData['items'] as $item) {
            if ($item['category'] !== 'Pendapatan') {
                continue;
            }

            $sheet->setCellValue('A' . $row, $item['code']);
            $sheet->setCellValue('B' . $row, $item['name']);
            $sheet->setCellValue('C' . $row, $item['amount']);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $totalPendapatanDisplay += $item['amount'];
            $row++;
        }

        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('A' . $row, 'Total Pendapatan');
        $sheet->setCellValue('C' . $row, $totalPendapatanDisplay);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $row += 2;

        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->setCellValue('A' . $row, 'Beban Biaya');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
        $row++;

        $totalBebanDisplay = 0;
        foreach ($labaRugiData['items'] as $item) {
            if ($item['category'] !== 'Beban') {
                continue;
            }

            $sheet->setCellValue('A' . $row, $item['code']);
            $sheet->setCellValue('B' . $row, $item['name']);
            $sheet->setCellValue('C' . $row, $item['amount']);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode($item['is_negative'] ? '#,##0_);(#,##0)' : '#,##0');
            $totalBebanDisplay += $item['amount'];
            $row++;
        }

        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('A' . $row, 'Total Beban Biaya');
        $sheet->setCellValue('C' . $row, $totalBebanDisplay);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode($totalBebanDisplay < 0 ? '#,##0_);(#,##0)' : '#,##0');
        $row += 2;

        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->setCellValue('A' . $row, 'Penghasilan (Biaya) Luar Usaha');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
        $row++;

        $totalExternalDisplay = 0;
        foreach ($labaRugiData['items'] as $item) {
            if ($item['category'] !== 'Penghasilan (Biaya) Luar Usaha') {
                continue;
            }

            $sheet->setCellValue('A' . $row, $item['code']);
            $sheet->setCellValue('B' . $row, $item['name']);
            $sheet->setCellValue('C' . $row, $item['amount']);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode($item['is_negative'] ? '#,##0_);(#,##0)' : '#,##0');
            $totalExternalDisplay += $item['amount'];
            $row++;
        }

        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('A' . $row, 'Total Penghasilan (Biaya) Luar Usaha');
        $sheet->setCellValue('C' . $row, $totalExternalDisplay);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode($totalExternalDisplay < 0 ? '#,##0_);(#,##0)' : '#,##0');
        $row += 2;

        $labaRugiBersih = $labaRugiData['labaRugiBersih'];
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('A' . $row, ($labaRugiBersih >= 0 ? 'Laba' : 'Rugi') . ' Bersih');
        $sheet->setCellValue('C' . $row, abs($labaRugiBersih));
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D0D0D0');
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');

        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'laba-rugi-' . strtolower($monthNames[$this->month]) . '-' . $this->year . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
