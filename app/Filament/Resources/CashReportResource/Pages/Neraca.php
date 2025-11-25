<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use App\Models\Coa;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        // Start date of current month
        $startOfCurrentMonth = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endOfCurrentMonth = Carbon::create($this->year, $this->month, 1)->endOfMonth();

        // Get Laba Rugi data first
        $labaRugiData = $this->getLabaRugiCalculation($startOfCurrentMonth, $endOfCurrentMonth);
        $sisaDanaTahunBerjalan = $labaRugiData['labaRugiBersih'];

        $data = Coa::query()
            ->select([
                'coa.id',
                'coa.code',
                'coa.name',
                'coa.type',
                'coa.group_coa_id',
                'group_coas.name as group_name',
                DB::raw('COALESCE(neraca_data.debit, 0) as debit'),
                DB::raw('COALESCE(neraca_data.credit, 0) as credit')
            ])
            ->leftJoin('group_coas', 'coa.group_coa_id', '=', 'group_coas.id')
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(debit_amount) as debit,
                        SUM(credit_amount) as credit
                    FROM journal_book_reports 
                    WHERE transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    AND journal_book_id = 3
                    GROUP BY coa_id
                ) as neraca_data"),
                'coa.id',
                '=',
                'neraca_data.coa_id'
            )
            ->where('coa.deleted_at', null)
            ->where('coa.type', 'kkp')
            ->whereNotIn('coa.id', [78, 118]) // Exclude 'Tidak Terklasifikasi'
            ->whereRaw("coa.code REGEXP '^AO-(([1-2][0-9]{2}|30[0-5])(\\.[1-5])?|(10[1-2])\\.[1-5]|1010|1011)$'")
            ->orderBy('group_coas.id')
            ->orderBy('coa.id')
            ->get();

        $neracaData = [
            'aktiva' => [],
            'pasiva' => [],
            'totalAktiva' => 0,
            'totalPasiva' => 0
        ];

        $currentGroup = null;
        $currentGroupName = '';
        $currentGroupTotal = 0;
        $currentGroupSide = null; // Track which side (aktiva/pasiva) the current group belongs to

        // Default array structure for all items
        $defaultItemStructure = [
            'code' => '',
            'name' => '',
            'amount' => 0,
            'is_negative' => false,
            'is_group_header' => false,
            'is_group_total' => false,
            'is_sisa_dana' => false
        ];

        foreach ($data as $row) {
            $amount = preg_match('/^AO-1/', $row->code) ?
                $row->debit - $row->credit :
                $row->credit - $row->debit;

            // Determine if this is aktiva or pasiva based on the account code
            $isAktiva = preg_match('/^AO-1/', $row->code);
            $target = $isAktiva ? 'aktiva' : 'pasiva';

            // If we're starting a new group or switching sides (aktiva/pasiva)
            if ($currentGroup !== $row->group_coa_id) {
                // Add the previous group's total if it exists
                if ($currentGroup !== null) {
                    $previousTarget = $currentGroupSide ?? $target;
                    $neracaData[$previousTarget][] = array_merge($defaultItemStructure, [
                        'name' => 'Total ' . $currentGroupName,
                        'amount' => abs($currentGroupTotal),
                        'is_negative' => $currentGroupTotal < 0,
                        'is_group_total' => true
                    ]);
                }

                // Start new group
                $currentGroup = $row->group_coa_id;
                $currentGroupName = $row->group_name ?? 'Lainnya';
                $currentGroupTotal = 0;
                $currentGroupSide = $target; // Set the side for the new group

                // Add group header
                $neracaData[$target][] = array_merge($defaultItemStructure, [
                    'name' => $currentGroupName,
                    'is_group_header' => true
                ]);
            }

            // Add the account
            $neracaData[$target][] = array_merge($defaultItemStructure, [
                'code' => $row->code,
                'name' => $row->name,
                'amount' => abs($amount),
                'is_negative' => $amount < 0
            ]);

            // Update totals
            $currentGroupTotal += $amount;
            if ($isAktiva) {
                $neracaData['totalAktiva'] += $amount;
            } else {
                $neracaData['totalPasiva'] += $amount;
            }
        }

        // Add the last group's total
        if ($currentGroup !== null && $currentGroupSide !== null) {
            $neracaData[$currentGroupSide][] = array_merge($defaultItemStructure, [
                'name' => 'Total ' . $currentGroupName,
                'amount' => abs($currentGroupTotal),
                'is_negative' => $currentGroupTotal < 0,
                'is_group_total' => true
            ]);
        }

        // Add Sisa Dana Tahun Berjalan to pasiva
        $neracaData['pasiva'][] = array_merge($defaultItemStructure, [
            'name' => 'Sisa (Lebih) Dana Tahun Berjalan',
            'amount' => abs($sisaDanaTahunBerjalan),
            'is_negative' => $sisaDanaTahunBerjalan < 0,
            'is_sisa_dana' => true
        ]);
        $neracaData['totalPasiva'] += $sisaDanaTahunBerjalan;

        return $neracaData;
    }

    private function getLabaRugiCalculation($startOfCurrentMonth, $endOfCurrentMonth)
    {
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
                    WHERE transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
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
            ->whereRaw("coa.code REGEXP '^AO-(4[0-9]{2}(\\.[1-6])?|501(\\.[1-4])?|50[0-9]|5[1-9][0-9]|6[0-9]{2}|70[0-2])$'")
            ->orderBy('coa.code')
            ->get();

        $totalPendapatan = 0;
        $totalBeban = 0;

        foreach ($data as $row) {
            $totalDebit = $row->neraca_awal_debit + $row->kas_besar_debit +
                $row->kas_kecil_debit + $row->bank_debit +
                $row->jurnal_umum_debit + $row->aje_debit;

            $totalKredit = $row->neraca_awal_kredit + $row->kas_besar_kredit +
                $row->kas_kecil_kredit + $row->bank_kredit +
                $row->jurnal_umum_kredit + $row->aje_kredit;

            if (preg_match('/^AO-4/', $row->code)) {
                // Pendapatan accounts (400 series)
                $amount = $totalKredit - $totalDebit;
                $totalPendapatan += $amount;
            } else {
                // Beban accounts (500-700 series)
                $amount = $totalDebit - $totalKredit;
                $totalBeban += $amount;
            }
        }

        return [
            'totalPendapatan' => $totalPendapatan,
            'totalBeban' => $totalBeban,
            'labaRugiBersih' => $totalPendapatan - $totalBeban
        ];
    }
}
