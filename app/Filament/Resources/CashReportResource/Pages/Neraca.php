<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use App\Services\NeracaReportService;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Neraca extends Page
{
    protected static string $resource = CashReportResource::class;

    protected static string $view = 'filament.resources.cash-report-resource.pages.neraca';

    protected static ?string $title = 'Laporan Neraca';
    protected static ?string $navigationLabel = 'Laporan Neraca';

    public $month;
    public $year;

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

                    // Redirect dengan query parameter untuk mempertahankan filter
                    $this->redirect(static::getUrl([
                        'month' => $this->month,
                        'year' => $this->year
                    ]));
                }),
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn() => $this->exportToExcel()),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn() => static::getResource()::getUrl('neraca-lajur')),
        ];
    }

    public function getNeracaData()
    {
        return app(NeracaReportService::class)->getMonthlyReport((int) $this->month, (int) $this->year);
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

        $title = 'LAPORAN NERACA - ' . strtoupper($monthNames[$this->month]) . ' ' . $this->year;

        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $neracaData = $this->getNeracaData();

        // Headers - Aktiva (Left) and Pasiva (Right)
        $sheet->setCellValue('A3', 'AKTIVA');
        $sheet->mergeCells('A3:C3');
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E0E0E0');

        $sheet->setCellValue('D3', 'PASIVA');
        $sheet->mergeCells('D3:F3');
        $sheet->getStyle('D3')->getFont()->setBold(true);
        $sheet->getStyle('D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E0E0E0');

        // Process data side by side
        $aktivaRow = 4;
        $pasivaRow = 4;

        // Write Aktiva data
        foreach ($neracaData['aktiva'] as $item) {
            if ($item['is_group_header']) {
                $sheet->setCellValue('A' . $aktivaRow, $item['name']);
                $sheet->mergeCells('A' . $aktivaRow . ':C' . $aktivaRow);
                $sheet->getStyle('A' . $aktivaRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $aktivaRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5F5F5');
            } elseif ($item['is_group_total']) {
                $sheet->setCellValue('B' . $aktivaRow, $item['name']);
                $sheet->setCellValue('C' . $aktivaRow, $item['amount']);
                $sheet->getStyle('B' . $aktivaRow)->getFont()->setBold(true);
                $sheet->getStyle('C' . $aktivaRow)->getFont()->setBold(true);
                $sheet->getStyle('C' . $aktivaRow)->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->setCellValue('A' . $aktivaRow, $item['code']);
                $sheet->setCellValue('B' . $aktivaRow, $item['name']);
                $sheet->setCellValue('C' . $aktivaRow, $item['amount']);
                $sheet->getStyle('C' . $aktivaRow)->getNumberFormat()->setFormatCode('#,##0');
                if ($item['is_negative']) {
                    $sheet->getStyle('C' . $aktivaRow)->getFont()->getColor()->setRGB('FF0000');
                }
            }
            $aktivaRow++;
        }

        // Total Aktiva
        $sheet->setCellValue('B' . $aktivaRow, 'TOTAL AKTIVA');
        $sheet->setCellValue('C' . $aktivaRow, $neracaData['totalAktiva']);
        $sheet->getStyle('B' . $aktivaRow . ':C' . $aktivaRow)->getFont()->setBold(true);
        $sheet->getStyle('B' . $aktivaRow . ':C' . $aktivaRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D0D0D0');
        $sheet->getStyle('C' . $aktivaRow)->getNumberFormat()->setFormatCode('#,##0');

        // Write Pasiva data
        foreach ($neracaData['pasiva'] as $item) {
            if ($item['is_group_header']) {
                $sheet->setCellValue('D' . $pasivaRow, $item['name']);
                $sheet->mergeCells('D' . $pasivaRow . ':F' . $pasivaRow);
                $sheet->getStyle('D' . $pasivaRow)->getFont()->setBold(true);
                $sheet->getStyle('D' . $pasivaRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5F5F5');
            } elseif ($item['is_group_total']) {
                $sheet->setCellValue('E' . $pasivaRow, $item['name']);
                $sheet->setCellValue('F' . $pasivaRow, $item['amount']);
                $sheet->getStyle('E' . $pasivaRow)->getFont()->setBold(true);
                $sheet->getStyle('F' . $pasivaRow)->getFont()->setBold(true);
                $sheet->getStyle('F' . $pasivaRow)->getNumberFormat()->setFormatCode('#,##0');
            } elseif ($item['is_sisa_dana']) {
                $sheet->setCellValue('E' . $pasivaRow, $item['name']);
                $sheet->setCellValue('F' . $pasivaRow, $item['amount']);
                $sheet->getStyle('E' . $pasivaRow)->getFont()->setBold(true);
                $sheet->getStyle('F' . $pasivaRow)->getFont()->setBold(true);
                $sheet->getStyle('F' . $pasivaRow)->getNumberFormat()->setFormatCode('#,##0');
                if ($item['is_negative']) {
                    $sheet->getStyle('F' . $pasivaRow)->getFont()->getColor()->setRGB('FF0000');
                }
            } else {
                $sheet->setCellValue('D' . $pasivaRow, $item['code']);
                $sheet->setCellValue('E' . $pasivaRow, $item['name']);
                $sheet->setCellValue('F' . $pasivaRow, $item['amount']);
                $sheet->getStyle('F' . $pasivaRow)->getNumberFormat()->setFormatCode('#,##0');
                if ($item['is_negative']) {
                    $sheet->getStyle('F' . $pasivaRow)->getFont()->getColor()->setRGB('FF0000');
                }
            }
            $pasivaRow++;
        }

        // Total Pasiva
        $sheet->setCellValue('E' . $pasivaRow, 'TOTAL PASIVA');
        $sheet->setCellValue('F' . $pasivaRow, $neracaData['totalPasiva']);
        $sheet->getStyle('E' . $pasivaRow . ':F' . $pasivaRow)->getFont()->setBold(true);
        $sheet->getStyle('E' . $pasivaRow . ':F' . $pasivaRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D0D0D0');
        $sheet->getStyle('F' . $pasivaRow)->getNumberFormat()->setFormatCode('#,##0');

        // Determine the maximum row to apply borders
        $maxRow = max($aktivaRow, $pasivaRow);

        // Apply borders to both sections
        $sheet->getStyle('A3:C' . $aktivaRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $sheet->getStyle('D3:F' . $pasivaRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'neraca-' . strtolower($monthNames[$this->month]) . '-' . $this->year . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
