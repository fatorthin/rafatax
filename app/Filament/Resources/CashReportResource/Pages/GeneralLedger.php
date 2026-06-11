<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use Filament\Resources\Pages\Page;

class GeneralLedger extends Page
{
    // InteractsWithForms is inherited from Page
    // HasForms is inherited from Page

    protected static string $resource = CashReportResource::class;

    protected static string $view = 'filament.resources.cash-report-resource.pages.general-ledger';

    protected static ?string $title = 'General Ledger';

    #[\Livewire\Attributes\Url]
    public ?string $bulan = null;

    #[\Livewire\Attributes\Url]
    public ?string $tahun = null;

    #[\Livewire\Attributes\Url]
    public ?string $cash_reference_id = null;

    public function mount(): void
    {
        $this->form->fill([
            'bulan' => $this->bulan ?? (string) now()->month,
            'tahun' => $this->tahun ?? (string) now()->year,
            'cash_reference_id' => $this->cash_reference_id,
        ]);
    }

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Section::make()
                    ->schema([
                        \Filament\Forms\Components\Select::make('bulan')
                            ->label('Bulan')
                            ->options([
                                '1' => 'Januari', '2' => 'Februari', '3' => 'Maret',
                                '4' => 'April', '5' => 'Mei', '6' => 'Juni',
                                '7' => 'Juli', '8' => 'Agustus', '9' => 'September',
                                '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
                            ])
                            ->default((string) now()->month)
                            ->required()
                            ->live(),
                        \Filament\Forms\Components\Select::make('tahun')
                            ->label('Tahun')
                            ->options(function () {
                                $years = \App\Models\CashReport::selectRaw('YEAR(transaction_date) as year')
                                    ->distinct()
                                    ->pluck('year', 'year')
                                    ->toArray();
                                $currentYear = now()->year;
                                if (!isset($years[$currentYear])) {
                                    $years[$currentYear] = $currentYear;
                                }
                                krsort($years);
                                return $years;
                            })
                            ->default((string) now()->year)
                            ->required()
                            ->live(),
                        \Filament\Forms\Components\Select::make('cash_reference_id')
                            ->label('Kas / Bank')
                            ->options(\App\Models\CashReference::all()->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Semua Kas / Bank')
                            ->live(),
                    ])
                    ->columns(3)
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $groupedTransactions = $this->getReports();
                    $coas = \App\Models\Coa::whereIn('id', $groupedTransactions->keys())->get()->keyBy('id');

                    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();

                    $row = 1;

                    // Title
                    $sheet->setCellValue('A' . $row, 'General Ledger');
                    $sheet->mergeCells("A{$row}:F{$row}");
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                    $row++;

                    // Period
                    $bulan = $this->bulan ?? now()->month;
                    $tahun = $this->tahun ?? now()->year;
                    $startDate = \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth()->format('Y-m-d');
                    $endDate = \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->format('Y-m-d');
                    
                    $sheet->setCellValue('A' . $row, "Periode: $startDate - $endDate");
                    $sheet->mergeCells("A{$row}:F{$row}");
                    $row += 2;

                    foreach ($groupedTransactions as $coaId => $transactions) {
                        $coa = $coas[$coaId] ?? null;
                        if (!$coa) continue;

                        // CoA Header
                        $sheet->setCellValue('A' . $row, "{$coa->code} - {$coa->name} ({$coa->type})");
                        $sheet->mergeCells("A{$row}:F{$row}");
                        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                        $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
                        $row++;

                        // Headers
                        $sheet->setCellValue('A' . $row, 'Tanggal');
                        $sheet->setCellValue('B' . $row, 'Deskripsi');
                        $sheet->setCellValue('C' . $row, 'Ref');
                        $sheet->setCellValue('D' . $row, 'Debit');
                        $sheet->setCellValue('E' . $row, 'Credit');
                        $sheet->setCellValue('F' . $row, 'Balance');
                        $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
                        $row++;

                        $balance = 0;
                        $totalDebit = 0;
                        $totalCredit = 0;

                        foreach ($transactions as $transaction) {
                            $debit = $transaction->display_debit ?? 0;
                            $credit = $transaction->display_credit ?? 0;
                            $balance += ($debit - $credit);
                            $totalDebit += $debit;
                            $totalCredit += $credit;

                            $sheet->setCellValue('A' . $row, \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y'));
                            $sheet->setCellValue('B' . $row, $transaction->description);
                            $sheet->setCellValue('C' . $row, $transaction->source ?? '-');
                            $sheet->setCellValue('D' . $row, $debit);
                            $sheet->setCellValue('E' . $row, $credit);
                            $sheet->setCellValue('F' . $row, $balance);
                            $row++;
                        }

                        // Footer Total
                        $sheet->setCellValue('A' . $row, 'Total');
                        $sheet->mergeCells("A{$row}:C{$row}");
                        $sheet->setCellValue('D' . $row, $totalDebit);
                        $sheet->setCellValue('E' . $row, $totalCredit);
                        $sheet->setCellValue('F' . $row, $balance);
                        $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);

                        $row += 2; // Space between CoAs
                    }

                    // Auto Size Columns
                    foreach (range('A', 'F') as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }

                    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

                    return response()->streamDownload(function () use ($writer) {
                        $writer->save('php://output');
                    }, 'GeneralLedger_' . now()->format('Ymd_His') . '.xlsx');
                }),
        ];
    }

    public function getReports()
    {
        $bulan = $this->bulan ?? now()->month;
        $tahun = $this->tahun ?? now()->year;
        
        $startDate = \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth()->format('Y-m-d');
        $endDate = \Carbon\Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->format('Y-m-d');
        
        $cashReferenceId = $this->cash_reference_id;

        // ── 1. Cash Reports (Kas Besar, Kas Kecil, Bank) ────────────────────
        $cashQuery = \App\Models\CashReport::query()
            ->select('cash_reports.*')
            ->join('coa', 'cash_reports.coa_id', '=', 'coa.id')
            ->with(['coa', 'cashReference'])
            ->whereBetween('cash_reports.transaction_date', [$startDate, $endDate]);

        if ($cashReferenceId) {
            $cashQuery->where('cash_reports.cash_reference_id', $cashReferenceId);
        }

        $cashTransactions = $cashQuery
            ->orderBy('coa.sort_order')
            ->orderBy('cash_reports.transaction_date')
            ->get()
            ->map(function ($t) {
                $t->source = $t->cashReference->name ?? '-';
                // Tukar tampilan: nominal debit di DB → tampil di kolom Kredit, dan sebaliknya
                $t->display_debit  = $t->credit_amount ?? 0;
                $t->display_credit = $t->debit_amount  ?? 0;
                return $t;
            });

        // ── 2. Journal Book Reports (Jurnal Umum, AJE, Jurnal Pendapatan) ──
        //    Diambil dari rentang tanggal yang sama dengan cash_reports.
        //    Tidak difilter by cash_reference_id (jurnal tidak terkait kas/bank).
        $journalLabels = [
            1 => 'Jurnal Umum',
            2 => 'AJE',
            4 => 'Jurnal Pendapatan',
        ];

        $journalTransactions = \App\Models\JournalBookReport::query()
            ->with('coa')
            ->whereIn('journal_book_id', [1, 2, 4])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get()
            ->map(function ($t) use ($journalLabels) {
                $t->source = $journalLabels[$t->journal_book_id] ?? 'Jurnal';
                // Jurnal: tampilan normal (tidak ditukar)
                $t->display_debit  = $t->debit_amount  ?? 0;
                $t->display_credit = $t->credit_amount ?? 0;
                return $t;
            });

        // ── 3. Neraca Awal (journal_book_id = 3) dari bulan sebelumnya ──────
        //    Di Neraca Lajur, neraca awal diambil dari bulan sebelum start_date.
        $prevMonthStart = \Carbon\Carbon::parse($startDate)->subMonth()->startOfMonth();
        $prevMonthEnd   = \Carbon\Carbon::parse($startDate)->subMonth()->endOfMonth();

        $neracaAwalTransactions = \App\Models\JournalBookReport::query()
            ->with('coa')
            ->where('journal_book_id', 3)
            ->whereBetween('transaction_date', [$prevMonthStart, $prevMonthEnd])
            ->get()
            ->map(function ($t) {
                $t->source = 'Neraca Awal';
                // Neraca Awal: tampilan normal (tidak ditukar)
                $t->display_debit  = $t->debit_amount  ?? 0;
                $t->display_credit = $t->credit_amount ?? 0;
                return $t;
            });

        $dynamicJPActions = $this->getDynamicJurnalPendapatan($startDate, $endDate);

        // ── 4. Gabungkan & group by coa_id, lalu sort by coa.code ───────────
        $allTransactions = $cashTransactions->toBase()
            ->merge($journalTransactions)
            ->merge($neracaAwalTransactions)
            ->merge($dynamicJPActions)
            ->sortBy('transaction_date');

        return $allTransactions->groupBy('coa_id')->sortBy(function ($transactions) {
            return $transactions->first()->coa->code ?? '999999';
        });
    }

    private function getPiutangToPendapatanMap(): array
    {
        return [
            188 => 119, // AO-103.6  -> AO-401   (Fee Bulanan)
            182 => 120, // AO-103.7  -> AO-401.1 (Fee SPT)
            183 => 121, // AO-103.8  -> AO-401.2 (Fee SP2DK)
            184 => 122, // AO-103.9  -> AO-401.3 (Fee Pembetulan)
            185 => 123, // AO-103.10 -> AO-401.4 (Fee Internal)
            186 => 124, // AO-103.11 -> AO-401.5 (Fee Restitusi)
            187 => 125, // AO-103.12 -> AO-401.6 (Fee Pemeriksaan)
        ];
    }

    private function getDynamicJurnalPendapatan(string $startDate, string $endDate): \Illuminate\Support\Collection
    {
        $transactions = collect();
        $coas = \App\Models\Coa::all()->keyBy('id');
        $map = $this->getPiutangToPendapatanMap();
        $coaBelumDiterimaId = 175; // AO-208 Pendapatan Yang Belum Diterima

        // ── 1. Dari MoU approved (cost_list_mous) ──
        // JP DEBIT  AO-103.x = piutang per CoA dari MoU
        // JP KREDIT AO-208   = total MoU (Pendapatan Belum Diterima)
        $mouRows = \Illuminate\Support\Facades\DB::table('cost_list_mous as clm')
            ->join('mous as m', 'm.id', '=', 'clm.mou_id')
            ->leftJoin('clients as c', 'c.id', '=', 'm.client_id')
            ->whereNull('m.deleted_at')
            ->whereNull('clm.deleted_at')
            ->where('m.status', 'approved')
            ->where('m.type', 'kkp')
            ->whereBetween('m.approved_date', [$startDate, $endDate])
            ->whereIn('clm.coa_id', array_keys($map))
            ->select([
                'clm.coa_id',
                'clm.total_amount',
                'm.approved_date',
                'm.mou_number',
                'c.company_name',
            ])
            ->get();

        foreach ($mouRows as $row) {
            $desc = "Piutang MoU No. " . $row->mou_number . ($row->company_name ? " - " . $row->company_name : "");
            
            // DEBIT: Piutang CoA (AO-103.x)
            $tDebit = new \stdClass();
            $tDebit->coa_id = $row->coa_id;
            $tDebit->transaction_date = $row->approved_date;
            $tDebit->description = $desc;
            $tDebit->source = 'Jurnal Pendapatan';
            $tDebit->display_debit = $row->total_amount;
            $tDebit->display_credit = 0;
            $tDebit->coa = $coas[$row->coa_id] ?? null;
            $transactions->push($tDebit);

            // KREDIT: Pendapatan Belum Diterima (AO-208)
            $tCredit = new \stdClass();
            $tCredit->coa_id = $coaBelumDiterimaId;
            $tCredit->transaction_date = $row->approved_date;
            $tCredit->description = $desc;
            $tCredit->source = 'Jurnal Pendapatan';
            $tCredit->display_debit = 0;
            $tCredit->display_credit = $row->total_amount;
            $tCredit->coa = $coas[$coaBelumDiterimaId] ?? null;
            $transactions->push($tCredit);
        }

        // ── 2. Dari cash_reports (CoA AO-103.x yang muncul di kolom kas/bank) ──
        // JP DEBIT  AO-208   = total kas diterima (pengurang accrual Pendapatan Belum Diterima)
        // JP KREDIT AO-401.x = nilai penerimaan per CoA (pengakuan pendapatan aktual)
        $cashRows = \Illuminate\Support\Facades\DB::table('cash_reports')
            ->whereNull('deleted_at')
            ->whereIn('coa_id', array_keys($map))
            ->whereIn('cash_reference_id', [1, 2, 3, 4, 5, 6, 7])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select([
                'coa_id',
                'debit_amount',
                'transaction_date',
                'description',
            ])
            ->get();

        foreach ($cashRows as $row) {
            $pendapatanCoaId = $map[$row->coa_id] ?? null;
            if (!$pendapatanCoaId) continue;

            $desc = "Penerimaan Piutang - " . ($row->description ?: 'Tanpa Keterangan');

            // DEBIT: Pendapatan Belum Diterima (AO-208)
            $tDebit = new \stdClass();
            $tDebit->coa_id = $coaBelumDiterimaId;
            $tDebit->transaction_date = $row->transaction_date;
            $tDebit->description = $desc;
            $tDebit->source = 'Jurnal Pendapatan';
            $tDebit->display_debit = $row->debit_amount;
            $tDebit->display_credit = 0;
            $tDebit->coa = $coas[$coaBelumDiterimaId] ?? null;
            $transactions->push($tDebit);

            // KREDIT: Pendapatan CoA (AO-401.x)
            $tCredit = new \stdClass();
            $tCredit->coa_id = $pendapatanCoaId;
            $tCredit->transaction_date = $row->transaction_date;
            $tCredit->description = $desc;
            $tCredit->source = 'Jurnal Pendapatan';
            $tCredit->display_debit = 0;
            $tCredit->display_credit = $row->debit_amount;
            $tCredit->coa = $coas[$pendapatanCoaId] ?? null;
            $transactions->push($tCredit);
        }

        return $transactions;
    }

    protected function getViewData(): array
    {
        $groupedTransactions = $this->getReports();

        return [
            'groupedTransactions' => $groupedTransactions,
            'coas' => \App\Models\Coa::whereIn('id', $groupedTransactions->keys())->get()->keyBy('id'),
        ];
    }
}
