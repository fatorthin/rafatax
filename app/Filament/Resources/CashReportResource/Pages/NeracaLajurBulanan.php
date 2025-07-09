<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use App\Models\Coa;
use App\Models\CashReport;
use App\Models\CashReference;
use App\Models\JournalBookReport;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class NeracaLajurBulanan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CashReportResource::class;
    protected static string $view = 'filament.resources.cash-report-resource.pages.neraca-lajur-bulanan';
    
    protected static ?string $title = 'Neraca Lajur Bulanan (KKP)';
    protected static ?string $navigationLabel = 'Neraca Lajur Bulanan (KKP)';

    public $month;
    public $year;
    public $kasbesarId;
    public $kaskecilId;

    public function mount(): void
    {
        $this->month = 1;
        $this->year = now()->year;
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
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                            4 => 'April', 5 => 'Mei', 6 => 'Juni',
                            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
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
            Action::make('viewNeraca')
                ->label('Lihat Laporan Neraca')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn () => static::getResource()::getUrl('neraca', ['month' => $this->month, 'year' => $this->year])),
            Action::make('viewLabaRugi')
                ->label('Lihat Laporan Laba Rugi')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn () => static::getResource()::getUrl('laba-rugi-bulanan', ['month' => $this->month, 'year' => $this->year])),
            Action::make('saveNeracaSetelahAJE')
                ->label('Simpan Data Neraca')
                ->icon('heroicon-o-document-check')
                ->color('success')
                ->url(fn () => url('/neraca-lajur/save-cutoff?month=' . $this->month . '&year=' . $this->year)),
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $url = url('/neraca-lajur/export?month=' . $this->month . '&year=' . $this->year);
                    return redirect()->away($url);
                }),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('index')),
        ];
    }

    protected function getLabaRugiData()
    {
        $data = $this->getTableQuery()->get();
        $labaRugiData = [];
        $totalPendapatan = 0;
        $totalBeban = 0;

        foreach ($data as $row) {
            $totalDebit = $row->neraca_awal_debit + $row->kas_besar_debit + 
                         $row->kas_kecil_debit + $row->bank_debit + 
                         $row->jurnal_umum_debit;
            
            $totalKredit = $row->neraca_awal_kredit + $row->kas_besar_kredit + 
                          $row->kas_kecil_kredit + $row->bank_kredit + 
                          $row->jurnal_umum_kredit;
            
            $selisihSebelumAJE = $totalDebit - $totalKredit;
            $selisihSetelahAJE = $selisihSebelumAJE + ($row->aje_debit - $row->aje_kredit);
            
            // Check if this is a Laba Rugi account
            if (preg_match('/^AO-(4[0-9]{2}(\.[1-6])?|501(\.[1-4])?|50[0-9]|5[1-9][0-9]|6[0-9]{2}|70[0-2])$/', $row->code)) {
                $amount = $selisihSetelahAJE;
                
                // Determine if this is pendapatan or beban based on the account code
                if (preg_match('/^AO-4/', $row->code)) {
                    // Pendapatan accounts (400 series)
                    $totalPendapatan += $amount;
                    $category = 'Pendapatan';
                } else {
                    // Beban accounts (500-700 series)
                    $totalBeban += $amount;
                    $category = 'Beban';
                }

                $labaRugiData[] = [
                    'code' => $row->code,
                    'name' => $row->name,
                    'category' => $category,
                    'amount' => abs($amount),
                    'is_debit' => $amount > 0
                ];
            }
        }

        return [
            'items' => $labaRugiData,
            'totalPendapatan' => abs($totalPendapatan),
            'totalBeban' => abs($totalBeban),
            'labaRugiBersih' => abs($totalPendapatan) - abs($totalBeban)
        ];
    }

    protected function getTableQuery(): Builder
    {
        // Start date of previous month
        $startOfPreviousMonth = Carbon::create($this->year, $this->month, 1)->subMonth()->startOfMonth();
        $endOfPreviousMonth = Carbon::create($this->year, $this->month, 1)->subMonth()->endOfMonth();
        
        // Current month date range
        $startOfCurrentMonth = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endOfCurrentMonth = Carbon::create($this->year, $this->month, 1)->endOfMonth();

        $query = Coa::query()
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
                DB::raw('COALESCE(aje_data.aje_kredit, 0) as aje_kredit'),
                DB::raw('COALESCE(neraca_awal_bulan_depan.neraca_awal_bulan_depan_debit, 0) as neraca_awal_bulan_depan_debit'),
                DB::raw('COALESCE(neraca_awal_bulan_depan.neraca_awal_bulan_depan_kredit, 0) as neraca_awal_bulan_depan_kredit')
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
                        debit_amount as kas_besar_kredit,
                        credit_amount as kas_besar_debit
                    FROM (
                        -- Regular case for all COA except AO-101
                        SELECT 
                            coa_id,
                            SUM(debit_amount) as debit_amount,
                            SUM(credit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 6
                        AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                        AND deleted_at IS NULL
                        AND coa_id != 75
                        GROUP BY coa_id
                        
                        UNION ALL
                        
                        -- Special case for AO-101
                        SELECT 
                            75 as coa_id,
                            SUM(credit_amount) as debit_amount,
                            SUM(debit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 6
                        AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
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
                        -- Regular case for all COA except AO-101.1
                        SELECT 
                            coa_id,
                            SUM(credit_amount) as debit_amount,
                            SUM(debit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 7
                        AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                        AND deleted_at IS NULL
                        AND coa_id != 76
                        GROUP BY coa_id
                        
                        UNION ALL
                        
                        -- Special case for AO-101.1
                        SELECT 
                            76 as coa_id,
                            SUM(debit_amount) as debit_amount,
                            SUM(credit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 7
                        AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
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
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1, 2, 3, 4, 5)
                                AND cr.deleted_at IS NULL
                                AND cref.deleted_at IS NULL
                                AND cr.coa_id = 94
                                AND cr.transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                            )
                            WHEN c.code = 'AO-101.2' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1, 2, 3, 4)
                                AND cr.deleted_at IS NULL
                                AND cref.deleted_at IS NULL
                                AND cr.coa_id IN (77, 82)
                                AND cr.transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                            )
                            WHEN c.code = 'AO-102.1' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 1
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.2' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 3
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.3' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 2
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.4' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 4
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.5' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 5
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            ELSE (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports
                                WHERE coa_id = c.id
                                AND cash_reference_id IN (1,2,3,4,5)
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                        END as bank_kredit,
                        CASE 
                            WHEN c.code = 'AO-1010' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1, 2, 3, 4, 5)
                                AND cr.deleted_at IS NULL
                                AND cref.deleted_at IS NULL
                                AND cr.coa_id = 94
                                AND cr.transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                            )
                            WHEN c.code = 'AO-101.2' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1, 2, 3, 4)
                                AND cr.deleted_at IS NULL
                                AND cref.deleted_at IS NULL
                                AND cr.coa_id IN (77, 82)
                                AND cr.transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                            )
                            WHEN c.code = 'AO-102.1' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 1
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.2' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 3
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.3' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 2
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.4' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 4
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.5' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 5
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            ELSE (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports
                                WHERE coa_id = c.id
                                AND cash_reference_id IN (1,2,3,4,5)
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
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
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(debit_amount) as neraca_awal_bulan_depan_debit,
                        SUM(credit_amount) as neraca_awal_bulan_depan_kredit
                    FROM journal_book_reports 
                    WHERE transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    AND journal_book_id = 3
                    GROUP BY coa_id
                ) as neraca_awal_bulan_depan"),
                'coa.id',
                '=',
                'neraca_awal_bulan_depan.coa_id'
            )
            ->where('coa.deleted_at', null)
            ->where('coa.type', 'kkp')
            ->orderBy('coa.id');

        return $query;
    }

    public function getTitle(): string
    {
        $monthName = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return 'Neraca Lajur Bulanan (KKP) - ' . $monthName[$this->month] . ' ' . $this->year;
    }

    private function getDataForExport()
    {
        // Get COA data first
        $coaData = Coa::where('deleted_at', null)
            ->where('type', 'kkp')
            ->orderBy('id')
            ->get();

        $startOfPreviousMonth = Carbon::create($this->year, $this->month, 1)->subMonth()->startOfMonth();
        $endOfPreviousMonth = Carbon::create($this->year, $this->month, 1)->subMonth()->endOfMonth();
        $startOfCurrentMonth = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endOfCurrentMonth = Carbon::create($this->year, $this->month, 1)->endOfMonth();

        // Get journal data for previous month
        $journalData = DB::table('journal_book_reports')
            ->select('coa_id', DB::raw('SUM(debit_amount) as debit'), DB::raw('SUM(credit_amount) as credit'))
            ->whereBetween('transaction_date', [$startOfPreviousMonth, $endOfPreviousMonth])
            ->whereNull('deleted_at')
            ->where('journal_book_id', 3)
            ->groupBy('coa_id')
            ->get()
            ->keyBy('coa_id');

        // Get cash reports data
        $cashReports = DB::table('cash_reports')
            ->select('coa_id', 'cash_reference_id', DB::raw('SUM(debit_amount) as debit'), DB::raw('SUM(credit_amount) as credit'))
            ->whereBetween('transaction_date', [$startOfCurrentMonth, $endOfCurrentMonth])
            ->whereNull('deleted_at')
            ->groupBy('coa_id', 'cash_reference_id')
            ->get()
            ->groupBy('coa_id');

        // Get journal umum data
        $jurnalUmumData = DB::table('journal_book_reports')
            ->select('coa_id', DB::raw('SUM(debit_amount) as debit'), DB::raw('SUM(credit_amount) as credit'))
            ->where('journal_book_id', 1)
            ->whereBetween('transaction_date', [$startOfCurrentMonth, $endOfCurrentMonth])
            ->whereNull('deleted_at')
            ->groupBy('coa_id')
            ->get()
            ->keyBy('coa_id');

        // Get AJE data
        $ajeData = DB::table('journal_book_reports')
            ->select('coa_id', DB::raw('SUM(debit_amount) as debit'), DB::raw('SUM(credit_amount) as credit'))
            ->where('journal_book_id', 2)
            ->whereBetween('transaction_date', [$startOfCurrentMonth, $endOfCurrentMonth])
            ->whereNull('deleted_at')
            ->groupBy('coa_id')
            ->get()
            ->keyBy('coa_id');

        $result = [];

        foreach ($coaData as $coa) {
            // Neraca awal from journal data
            $journal = $journalData->get($coa->id);
            $neracaAwalDebit = $journal ? $journal->debit : 0;
            $neracaAwalKredit = $journal ? $journal->credit : 0;

            // Cash data processing
            $cashData = $cashReports->get($coa->id, collect());
            $kasBesarDebit = $kasBesarKredit = $kasKecilDebit = $kasKecilKredit = 0;
            $bankDebit = $bankKredit = 0;

            foreach ($cashData as $cash) {
                if ($cash->cash_reference_id == 6) { // Kas Besar
                    if ($coa->id == 75) { // AO-101 special case
                        $kasBesarDebit += $cash->credit;
                        $kasBesarKredit += $cash->debit;
                    } else {
                        $kasBesarDebit += $cash->credit;
                        $kasBesarKredit += $cash->debit;
                    }
                } elseif ($cash->cash_reference_id == 7) { // Kas Kecil
                    if ($coa->id == 76) { // AO-101.1 special case
                        $kasKecilDebit += $cash->debit;
                        $kasKecilKredit += $cash->credit;
                    } else {
                        $kasKecilDebit += $cash->credit;
                        $kasKecilKredit += $cash->debit;
                    }
                } elseif (in_array($cash->cash_reference_id, [1,2,3,4,5])) { // Bank
                    $bankDebit += $cash->debit;
                    $bankKredit += $cash->credit;
                }
            }

            // Apply special bank logic for specific COA codes
            if (in_array($coa->code, ['AO-102.1', 'AO-102.2', 'AO-102.3', 'AO-102.4', 'AO-102.5'])) {
                // Swap for these COA codes
                $temp = $bankDebit;
                $bankDebit = $bankKredit;
                $bankKredit = $temp;
            }

            // Journal umum
            $jurnal = $jurnalUmumData->get($coa->id);
            $jurnalUmumDebit = $jurnal ? $jurnal->debit : 0;
            $jurnalUmumKredit = $jurnal ? $jurnal->credit : 0;

            // AJE
            $aje = $ajeData->get($coa->id);
            $ajeDebit = $aje ? $aje->debit : 0;
            $ajeKredit = $aje ? $aje->credit : 0;

            $result[] = (object) [
                'id' => $coa->id,
                'code' => $coa->code,
                'name' => $coa->name,
                'neraca_awal_debit' => $neracaAwalDebit,
                'neraca_awal_kredit' => $neracaAwalKredit,
                'kas_besar_debit' => $kasBesarDebit,
                'kas_besar_kredit' => $kasBesarKredit,
                'kas_kecil_debit' => $kasKecilDebit,
                'kas_kecil_kredit' => $kasKecilKredit,
                'bank_debit' => $bankDebit,
                'bank_kredit' => $bankKredit,
                'jurnal_umum_debit' => $jurnalUmumDebit,
                'jurnal_umum_kredit' => $jurnalUmumKredit,
                'aje_debit' => $ajeDebit,
                'aje_kredit' => $ajeKredit,
            ];
        }

        return collect($result);
    }

    public function exportToExcel()
    {
        // Disable memory limit and increase execution time
        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', $this->getTitle());
        $sheet->mergeCells('A1:U1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Headers - simplified approach
        $sheet->setCellValue('A3', 'Kode Akun');
        $sheet->setCellValue('B3', 'Neraca Awal');
        $sheet->setCellValue('D3', 'Kas Besar');
        $sheet->setCellValue('F3', 'Kas Kecil');
        $sheet->setCellValue('H3', 'Bank');
        $sheet->setCellValue('J3', 'Jurnal Umum');
        $sheet->setCellValue('L3', 'Neraca Sebelum AJE');
        $sheet->setCellValue('N3', 'AJE');
        $sheet->setCellValue('P3', 'Neraca Setelah AJE');
        $sheet->setCellValue('R3', 'Neraca');
        $sheet->setCellValue('T3', 'Laba Rugi');

        // Sub headers
        $subHeaders = ['Debit', 'Kredit'];
        $cols = ['B', 'D', 'F', 'H', 'J', 'L', 'N', 'P', 'R', 'T'];
        foreach ($cols as $col) {
            $sheet->setCellValue($col.'4', $subHeaders[0]);
            $sheet->setCellValue(chr(ord($col)+1).'4', $subHeaders[1]);
        }

        // Merge cells
        $sheet->mergeCells('A3:A4');
        foreach ($cols as $col) {
            $sheet->mergeCells($col.'3:'.chr(ord($col)+1).'3');
        }

        // Apply header styling
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C3C1C1']],
        ];
        $sheet->getStyle('A3:U4')->applyFromArray($headerStyle);

        // Get data with simplified query - limit records to avoid timeout
        $data = $this->getDataForExport();
        
        if ($data->isEmpty()) {
            $sheet->setCellValue('A5', 'Tidak ada data untuk periode ini');
            $sheet->mergeCells('A5:U5');
            $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        } else {
            $row = 5;
            foreach ($data as $item) {
                // Calculate values
                $totalDebit = $item->neraca_awal_debit + $item->kas_besar_debit + $item->kas_kecil_debit + $item->bank_debit + $item->jurnal_umum_debit;
                $totalKredit = $item->neraca_awal_kredit + $item->kas_besar_kredit + $item->kas_kecil_kredit + $item->bank_kredit + $item->jurnal_umum_kredit;
                
                $selisihSebelumAJE = $totalDebit - $totalKredit;
                $neracaSebelumAJEDebit = max(0, $selisihSebelumAJE);
                $neracaSebelumAJEKredit = max(0, -$selisihSebelumAJE);

                $selisihSetelahAJE = $selisihSebelumAJE + ($item->aje_debit - $item->aje_kredit);
                $neracaSetelahAJEDebit = max(0, $selisihSetelahAJE);
                $neracaSetelahAJEKredit = max(0, -$selisihSetelahAJE);

                // Neraca (AO-101 to AO-305, including AO-101.1 to AO-101.5 and AO-102.1 to AO-102.5)
                $showInNeraca = preg_match('/^AO-(([1-2][0-9]{2}|30[0-5])(\.[1-5])?|(10[1-2])\.[1-5])$/', $item->code);
                $neracaDebit = $showInNeraca ? $neracaSetelahAJEDebit : 0;
                $neracaKredit = $showInNeraca ? $neracaSetelahAJEKredit : 0;

                // Laba Rugi (AO-401 to AO-702)
                $showInLabaRugi = preg_match('/^AO-(4[0-9]{2}(\.[1-6])?|501(\.[1-4])?|50[0-9]|5[1-9][0-9]|6[0-9]{2}|70[0-2])$/', $item->code);
                $labaRugiDebit = $showInLabaRugi ? $neracaSetelahAJEDebit : 0;
                $labaRugiKredit = $showInLabaRugi ? $neracaSetelahAJEKredit : 0;

                // Set data in cells
                $sheet->setCellValue('A'.$row, $item->code.' '.$item->name);
                $sheet->setCellValue('B'.$row, $item->neraca_awal_debit);
                $sheet->setCellValue('C'.$row, $item->neraca_awal_kredit);
                $sheet->setCellValue('D'.$row, $item->kas_besar_debit);
                $sheet->setCellValue('E'.$row, $item->kas_besar_kredit);
                $sheet->setCellValue('F'.$row, $item->kas_kecil_debit);
                $sheet->setCellValue('G'.$row, $item->kas_kecil_kredit);
                $sheet->setCellValue('H'.$row, $item->bank_debit);
                $sheet->setCellValue('I'.$row, $item->bank_kredit);
                $sheet->setCellValue('J'.$row, $item->jurnal_umum_debit);
                $sheet->setCellValue('K'.$row, $item->jurnal_umum_kredit);
                $sheet->setCellValue('L'.$row, $neracaSebelumAJEDebit);
                $sheet->setCellValue('M'.$row, $neracaSebelumAJEKredit);
                $sheet->setCellValue('N'.$row, $item->aje_debit);
                $sheet->setCellValue('O'.$row, $item->aje_kredit);
                $sheet->setCellValue('P'.$row, $neracaSetelahAJEDebit);
                $sheet->setCellValue('Q'.$row, $neracaSetelahAJEKredit);
                $sheet->setCellValue('R'.$row, $neracaDebit);
                $sheet->setCellValue('S'.$row, $neracaKredit);
                $sheet->setCellValue('T'.$row, $labaRugiDebit);
                $sheet->setCellValue('U'.$row, $labaRugiKredit);

                $row++;
            }

            // Add totals
            $totalRow = $row;
            $sheet->setCellValue('A'.$totalRow, 'Total');
            $sheet->getStyle('A'.$totalRow.':U'.$totalRow)->applyFromArray($headerStyle);
            
            // Calculate totals
            $columns = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U'];
            foreach ($columns as $col) {
                $sheet->setCellValue($col.$totalRow, '=SUM('.$col.'5:'.$col.($totalRow-1).')');
            }

            // Apply borders and number format
            $sheet->getStyle('A5:U'.($totalRow-1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
            $sheet->getStyle('B5:U'.$totalRow)->getNumberFormat()->setFormatCode('#,##0');
        }

        // Auto-size columns
        foreach (range('A', 'U') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Prepare download
        $filename = 'neraca-lajur-bulanan-' . strtolower(Carbon::create($this->year, $this->month, 1)->format('F-Y')) . '.xlsx';
        
        // Clear output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        
        // Save and output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
} 