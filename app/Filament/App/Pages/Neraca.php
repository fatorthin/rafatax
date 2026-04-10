<?php

namespace App\Filament\App\Pages;

use App\Models\Coa;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use App\Services\NeracaReportService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Neraca extends Page
{
    protected static string $view = 'filament.app.pages.neraca';
    protected static ?string $title = 'Laporan Neraca';

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
                    $this->redirect(route('filament.app.pages.neraca', ['month' => $this->month, 'year' => $this->year]));
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

    public function getNeracaData()
    {
        $startOfCurrentMonth = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endOfCurrentMonth = Carbon::create($this->year, $this->month, 1)->endOfMonth();

        // Get Laba Rugi data first for Sisa Dana calculation
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

        // Headers
        $sheet->setCellValue('A3', 'AKTIVA');
        $sheet->mergeCells('A3:C3');
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');

        $sheet->setCellValue('D3', 'PASIVA');
        $sheet->mergeCells('D3:F3');
        $sheet->getStyle('D3')->getFont()->setBold(true);
        $sheet->getStyle('D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');

        $aktivaRow = 4;
        $pasivaRow = 4;

        foreach ($neracaData['aktiva'] as $item) {
            if ($item['is_group_header']) {
                $sheet->setCellValue('A' . $aktivaRow, $item['name']);
                $sheet->getStyle('A' . $aktivaRow)->getFont()->setBold(true);
            } elseif ($item['is_group_total']) {
                $sheet->setCellValue('B' . $aktivaRow, $item['name']);
                $sheet->setCellValue('C' . $aktivaRow, $item['amount']);
                $sheet->getStyle('B' . $aktivaRow . ':C' . $aktivaRow)->getFont()->setBold(true);
                $sheet->getStyle('C' . $aktivaRow)->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->setCellValue('A' . $aktivaRow, $item['code']);
                $sheet->setCellValue('B' . $aktivaRow, $item['name']);
                $sheet->setCellValue('C' . $aktivaRow, $item['amount']);
                $sheet->getStyle('C' . $aktivaRow)->getNumberFormat()->setFormatCode('#,##0');
            }
            $aktivaRow++;
        }

        $sheet->setCellValue('B' . $aktivaRow, 'TOTAL AKTIVA');
        $sheet->setCellValue('C' . $aktivaRow, $neracaData['totalAktiva']);
        $sheet->getStyle('B' . $aktivaRow . ':C' . $aktivaRow)->getFont()->setBold(true);
        $sheet->getStyle('B' . $aktivaRow . ':C' . $aktivaRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D0D0D0');
        $sheet->getStyle('C' . $aktivaRow)->getNumberFormat()->setFormatCode('#,##0');

        foreach ($neracaData['pasiva'] as $item) {
            if ($item['is_group_header']) {
                $sheet->setCellValue('D' . $pasivaRow, $item['name']);
                $sheet->getStyle('D' . $pasivaRow)->getFont()->setBold(true);
            } elseif ($item['is_group_total']) {
                $sheet->setCellValue('E' . $pasivaRow, $item['name']);
                $sheet->setCellValue('F' . $pasivaRow, $item['amount']);
                $sheet->getStyle('E' . $pasivaRow . ':F' . $pasivaRow)->getFont()->setBold(true);
                $sheet->getStyle('F' . $pasivaRow)->getNumberFormat()->setFormatCode('#,##0');
            } elseif ($item['is_sisa_dana']) {
                $sheet->setCellValue('E' . $pasivaRow, $item['name']);
                $sheet->setCellValue('F' . $pasivaRow, $item['amount']);
                $sheet->getStyle('F' . $pasivaRow)->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->setCellValue('D' . $pasivaRow, $item['code']);
                $sheet->setCellValue('E' . $pasivaRow, $item['name']);
                $sheet->setCellValue('F' . $pasivaRow, $item['amount']);
                $sheet->getStyle('F' . $pasivaRow)->getNumberFormat()->setFormatCode('#,##0');
            }
            $pasivaRow++;
        }

        $sheet->setCellValue('E' . $pasivaRow, 'TOTAL PASIVA');
        $sheet->setCellValue('F' . $pasivaRow, $neracaData['totalPasiva']);
        $sheet->getStyle('E' . $pasivaRow . ':F' . $pasivaRow)->getFont()->setBold(true);
        $sheet->getStyle('E' . $pasivaRow . ':F' . $pasivaRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D0D0D0');
        $sheet->getStyle('F' . $pasivaRow)->getNumberFormat()->setFormatCode('#,##0');

        $maxRow = max($aktivaRow, $pasivaRow);
        $sheet->getStyle('A3:C' . $aktivaRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
        $sheet->getStyle('D3:F' . $pasivaRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

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

    private function getLabaRugiCalculation($startOfCurrentMonth, $endOfCurrentMonth)
    {
        $data = Coa::query()
            ->select([
                'coa.id',
                'coa.code',
                'coa.name',
                DB::raw('COALESCE(SUM(CASE WHEN journal_book_id = 3 THEN debit_amount ELSE 0 END), 0) as neraca_awal_debit'),
                DB::raw('COALESCE(SUM(CASE WHEN journal_book_id = 3 THEN credit_amount ELSE 0 END), 0) as neraca_awal_kredit'),
                DB::raw('COALESCE(SUM(CASE WHEN journal_book_id = 1 THEN debit_amount ELSE 0 END), 0) as jurnal_umum_debit'),
                DB::raw('COALESCE(SUM(CASE WHEN journal_book_id = 1 THEN credit_amount ELSE 0 END), 0) as jurnal_umum_kredit'),
                DB::raw('COALESCE(SUM(CASE WHEN journal_book_id = 2 THEN debit_amount ELSE 0 END), 0) as aje_debit'),
                DB::raw('COALESCE(SUM(CASE WHEN journal_book_id = 2 THEN credit_amount ELSE 0 END), 0) as aje_kredit')
            ])
            ->leftJoin('journal_book_reports', 'coa.id', '=', 'journal_book_reports.coa_id')
            ->where('coa.deleted_at', null)
            ->where('coa.type', 'kkp')
            ->whereRaw("coa.code REGEXP '^AO-(4[0-9]{2}(\\.[1-6])?|501(\\.[1-4])?|50[0-9]|5[1-9][0-9]|6[0-9]{2}|70[0-2])$'")
            ->groupBy('coa.id', 'coa.code', 'coa.name')
            ->orderBy('coa.code')
            ->get();

        $totalPendapatan = 0;
        $totalBeban = 0;

        foreach ($data as $row) {
            $totalDebit = $row->neraca_awal_debit + $row->jurnal_umum_debit;
            $totalKredit = $row->neraca_awal_kredit + $row->jurnal_umum_kredit;
            $selisihSebelumAJE = $totalDebit - $totalKredit;
            $selisihSetelahAJE = $selisihSebelumAJE + ($row->aje_debit - $row->aje_kredit);

            if (preg_match('/^AO-4/', $row->code)) {
                $totalPendapatan += $selisihSetelahAJE;
            } else {
                $totalBeban += $selisihSetelahAJE;
            }
        }

        return [
            'totalPendapatan' => abs($totalPendapatan),
            'totalBeban' => abs($totalBeban),
            'labaRugiBersih' => abs($totalPendapatan) - abs($totalBeban)
        ];
    }
}
