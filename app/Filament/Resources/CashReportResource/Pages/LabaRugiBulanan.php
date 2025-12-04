<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use App\Models\Coa;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LabaRugiBulanan extends Page
{
    protected static string $resource = CashReportResource::class;

    protected static string $view = 'filament.resources.cash-report-resource.pages.laba-rugi-bulanan';

    protected static ?string $title = 'Laporan Laba Rugi Bulanan';
    protected static ?string $navigationLabel = 'Laporan Laba Rugi Bulanan';
    protected static ?string $slug = 'laba-rugi-bulanan';

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
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn() => $this->exportToExcel()),
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
                }),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn() => static::getResource()::getUrl('neraca-lajur')),
        ];
    }

    public function getLabaRugiData()
    {
        // Start date of previous month
        $startOfPreviousMonth = Carbon::create($this->year, $this->month, 1)->subMonth()->startOfMonth();
        $endOfPreviousMonth = Carbon::create($this->year, $this->month, 1)->subMonth()->endOfMonth();

        // Current month date range
        $startOfCurrentMonth = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endOfCurrentMonth = Carbon::create($this->year, $this->month, 1)->endOfMonth();

        $data = Coa::query()
            ->select([
                'coa.id',
                'coa.code',
                'coa.name',
                'coa.type',
                DB::raw('COALESCE(journal_data.neraca_awal_debit, 0) as neraca_awal_debit'),
                DB::raw('COALESCE(journal_data.neraca_awal_kredit, 0) as neraca_awal_kredit'),
                DB::raw('COALESCE(kas_besar_data.kas_besar_debit, 0) as kas_besar_debit'),
                DB::raw('COALESCE(kas_besar_data.kas_besar_kredit, 0) as kas_besar_kredit'),
                DB::raw('COALESCE(kas_kecil_data.kas_kecil_debit, 0) as kas_kecil_debit'),
                DB::raw('COALESCE(kas_kecil_data.kas_kecil_kredit, 0) as kas_kecil_kredit'),
                DB::raw('COALESCE(bank_data.bank_debit, 0) as bank_debit'),
                DB::raw('COALESCE(bank_data.bank_kredit, 0) as bank_kredit'),
                DB::raw('COALESCE(jurnal_umum_data.jurnal_umum_debit, 0) as jurnal_umum_debit'),
                DB::raw('COALESCE(jurnal_umum_data.jurnal_umum_kredit, 0) as jurnal_umum_kredit'),
                DB::raw('COALESCE(aje_data.aje_debit, 0) as aje_debit'),
                DB::raw('COALESCE(aje_data.aje_kredit, 0) as aje_kredit')
            ])
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(debit_amount) as neraca_awal_debit,
                        SUM(credit_amount) as neraca_awal_kredit
                    FROM journal_book_reports 
                    WHERE transaction_date BETWEEN '{$startOfPreviousMonth}' AND '{$endOfPreviousMonth}'
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
                        SUM(credit_amount) as kas_besar_debit,
                        SUM(debit_amount) as kas_besar_kredit
                    FROM cash_reports
                    WHERE cash_reference_id = 6
                    AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as kas_besar_data"),
                'coa.id',
                '=',
                'kas_besar_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(credit_amount) as kas_kecil_debit,
                        SUM(debit_amount) as kas_kecil_kredit
                    FROM cash_reports
                    WHERE cash_reference_id = 7
                    AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as kas_kecil_data"),
                'coa.id',
                '=',
                'kas_kecil_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(credit_amount) as bank_debit,
                        SUM(debit_amount) as bank_kredit
                    FROM cash_reports
                    WHERE cash_reference_id IN (1,2,3,4,5)
                    AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
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
                    AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
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
                    AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as aje_data"),
                'coa.id',
                '=',
                'aje_data.coa_id'
            )
            ->where('coa.deleted_at', null)
            ->where('coa.type', 'kkp')
            ->orderBy('coa.code')
            ->get();

        $labaRugiData = [];
        $totalPendapatan = 0;
        $totalBeban = 0;
        // dd($data->toArray());

        foreach ($data as $row) {
            $totalDebit = $row->neraca_awal_debit + $row->kas_besar_debit +
                $row->kas_kecil_debit + $row->bank_debit +
                $row->jurnal_umum_debit + $row->aje_debit;

            $totalKredit = $row->neraca_awal_kredit + $row->kas_besar_kredit +
                $row->kas_kecil_kredit + $row->bank_kredit +
                $row->jurnal_umum_kredit + $row->aje_kredit;

            // Check if this is a Laba Rugi account
            if (preg_match('/^AO-(4[0-9]{2}(\.[1-6])?|501(\.[1-4])?|50[0-9]|5[1-9][0-9]|6[0-9]{2}|70[0-2])$/', $row->code)) {
                // Determine if this is pendapatan or beban based on the account code
                if (preg_match('/^AO-4/', $row->code)) {
                    // Pendapatan accounts (400 series)
                    $amount = $totalKredit - $totalDebit;
                    $totalPendapatan += $amount;
                    $category = 'Pendapatan';
                } elseif (preg_match('/^AO-6/', $row->code)) {
                    // Penghasilan Luar Usaha (600 series) - treated as pendapatan (external income)
                    $amount = $totalKredit - $totalDebit;
                    $totalPendapatan += $amount;
                    $category = 'Penghasilan (Biaya) Luar Usaha';
                } elseif (preg_match('/^AO-7/', $row->code)) {
                    // Biaya Luar Usaha (700 series) - treated as beban (external expense)
                    $amount = $totalDebit - $totalKredit;
                    $totalBeban += $amount;
                    $category = 'Penghasilan (Biaya) Luar Usaha';
                } else {
                    // Beban accounts (500 series and others)
                    // Untuk beban, jika ada debit maka nilainya minus
                    $amount = $totalDebit - $totalKredit;
                    $totalBeban += $amount;
                    $category = 'Beban';
                }

                $labaRugiData[] = [
                    'code' => $row->code,
                    'name' => $row->name,
                    'category' => $category,
                    'amount' => $amount,
                    'is_negative' => $amount < 0
                ];
            }
        }



        return [
            'items' => collect($labaRugiData)->sortBy('code'),
            'totalPendapatan' => abs($totalPendapatan),
            'totalBeban' => abs($totalBeban),
            'labaRugiBersih' => $totalPendapatan - $totalBeban
        ];
    }

    public function exportToExcel(): StreamedResponse
    {
        $bulan = [
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

        $data = $this->getLabaRugiData();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'Laporan Laba Rugi');
        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'Periode ' . $bulan[$this->month] . ' ' . $this->year);

        // Headers
        $sheet->setCellValue('A4', 'Kode Akun');
        $sheet->setCellValue('B4', 'Nama Akun');
        $sheet->setCellValue('C4', 'Jumlah');

        // Style the headers
        $sheet->getStyle('A1:C1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:C2')->getFont()->setSize(12);
        $sheet->getStyle('A4:C4')->getFont()->setBold(true);
        $sheet->getStyle('A4:C4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB');

        // Current row
        $row = 5;

        // Pendapatan Section (operasional)
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", 'Pendapatan');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
        $row++;

        $sectionPendapatanTotal = 0;
        foreach ($data['items'] as $item) {
            if ($item['category'] === 'Pendapatan') {
                $sheet->setCellValue("A{$row}", $item['code']);
                $sheet->setCellValue("B{$row}", $item['name']);
                $sheet->setCellValue("C{$row}", $item['amount']);
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
                $sectionPendapatanTotal += $item['amount'];
                $row++;
            }
        }

        // Total Pendapatan (operasional)
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", 'Total Pendapatan');
        $sheet->setCellValue("C{$row}", $sectionPendapatanTotal);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row++;
        $row++;

        // Beban Section (operasional)
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", 'Beban Biaya');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
        $row++;

        $sectionBebanTotal = 0;
        foreach ($data['items'] as $item) {
            if ($item['category'] === 'Beban') {
                $sheet->setCellValue("A{$row}", $item['code']);
                $sheet->setCellValue("B{$row}", $item['name']);

                // Format negative numbers with parentheses
                if ($item['is_negative']) {
                    $sheet->setCellValue("C{$row}", $item['amount']);
                    $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0_);(#,##0)');
                } else {
                    $sheet->setCellValue("C{$row}", $item['amount']);
                    $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
                }

                $sectionBebanTotal += $item['amount'];
                $row++;
            }
        }

        // Total Beban (operasional)
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", 'Total Beban Biaya');
        $sheet->setCellValue("C{$row}", $sectionBebanTotal);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        if ($sectionBebanTotal < 0) {
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0_);(#,##0)');
        } else {
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        }
        $row++;
        $row++;

        // Penghasilan / Biaya Luar Usaha Section (6xx / 7xx) - placed after Beban
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", 'Penghasilan (Biaya) Luar Usaha');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
        $row++;

        $sectionPenghasilanLuarTotal = 0;
        foreach ($data['items'] as $item) {
            if ($item['category'] === 'Penghasilan (Biaya) Luar Usaha') {
                $sheet->setCellValue("A{$row}", $item['code']);
                $sheet->setCellValue("B{$row}", $item['name']);

                if ($item['is_negative']) {
                    $sheet->setCellValue("C{$row}", $item['amount']);
                    $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0_);(#,##0)');
                } else {
                    $sheet->setCellValue("C{$row}", $item['amount']);
                    $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
                }

                $sectionPenghasilanLuarTotal += $item['amount'];
                $row++;
            }
        }

        // Total Penghasilan (Biaya) Luar Usaha (subtotal)
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", 'Total Penghasilan (Biaya) Luar Usaha');
        $sheet->setCellValue("C{$row}", $sectionPenghasilanLuarTotal);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        if ($sectionPenghasilanLuarTotal < 0) {
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0_);(#,##0)');
        } else {
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        }
        $row++;

        // Laba/Rugi Bersih (includes operasional + luar usaha)
        $labaRugi = $data['labaRugiBersih'];
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", ($labaRugi >= 0 ? 'Laba' : 'Rugi') . ' Bersih');
        $sheet->setCellValue("C{$row}", abs($labaRugi));
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("C{$row}")->getFont()->getColor()->setRGB($labaRugi >= 0 ? '008000' : 'FF0000');

        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create the Excel file
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, "Laporan_Laba_Rugi_{$bulan[$this->month]}_{$this->year}.xlsx");
    }
}
