<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use App\Models\Coa;
use App\Models\JournalBookReport;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class KelolaSaldoAwalEntitas extends Page
{
    protected static string $resource = CashReportResource::class;

    protected static string $view = 'filament.resources.cash-report-resource.pages.kelola-saldo-awal-entitas';

    protected static ?string $title = 'Kelola Saldo Awal Entitas (Cut-Off)';

    public int $year = 2026;
    public int $month = 4; // Default April 2026 (Cut-off Pemisahan)
    public string $entity = 'PT'; // 'PT' or 'KKP'
    public string $search = '';
    public ?int $selectedGroup = null;

    /**
     * Array of CoAs for balance sheet accounts.
     * [
     *   coa_id => [
     *     'coa_id' => 76,
     *     'code' => 'AO-101',
     *     'name' => 'Kas',
     *     'group_id' => 10,
     *     'group_name' => 'Aktiva Lancar',
     *     'ref_debit' => 0.0,
     *     'ref_credit' => 0.0,
     *     'debit' => 0.0,
     *     'credit' => 0.0,
     *   ]
     * ]
     */
    public array $items = [];

    public function mount(): void
    {
        $this->year   = (int) request('year', 2026);
        $this->month  = (int) request('month', 4);
        $this->entity = strtoupper(request('entity', 'PT'));

        if (!in_array($this->entity, ['PT', 'KKP'], true)) {
            $this->entity = 'PT';
        }

        $this->loadData();
    }

    public function loadData(): void
    {
        $prevCarbon = Carbon::create($this->year, $this->month, 1)->subMonth();
        $prevYear   = $prevCarbon->year;
        $prevMonth  = $prevCarbon->month;

        // Balance sheet groups (Aktiva, Kewajiban, Ekuitas)
        $balanceSheetGroupIds = [10, 11, 12, 20, 21, 30];

        $coas = DB::table('coa')
            ->join('group_coas', 'group_coas.id', '=', 'coa.group_coa_id')
            ->whereIn('coa.group_coa_id', $balanceSheetGroupIds)
            ->whereNull('coa.deleted_at')
            ->select('coa.id', 'coa.code', 'coa.name', 'coa.group_coa_id', 'group_coas.name as group_name')
            ->orderBy('coa.group_coa_id')
            ->orderBy('coa.code')
            ->get();

        // 1. Ambil data Saldo Konsolidasi bulan sebelumnya (seluruh kepemilikan)
        $consolidatedRows = DB::table('journal_book_reports')
            ->where('journal_book_id', 3)
            ->whereYear('transaction_date', $prevYear)
            ->whereMonth('transaction_date', $prevMonth)
            ->whereNull('deleted_at')
            ->select('coa_id', DB::raw('SUM(debit_amount) as debit, SUM(credit_amount) as credit'))
            ->groupBy('coa_id')
            ->get()
            ->keyBy('coa_id');

        // 2. Ambil data existing untuk entitas yang sedang dipilih ($this->entity)
        $entityRows = DB::table('journal_book_reports')
            ->where('journal_book_id', 3)
            ->whereYear('transaction_date', $prevYear)
            ->whereMonth('transaction_date', $prevMonth)
            ->whereRaw('UPPER(kepemilikan) = ?', [$this->entity])
            ->whereNull('deleted_at')
            ->select('coa_id', DB::raw('SUM(debit_amount) as debit, SUM(credit_amount) as credit'))
            ->groupBy('coa_id')
            ->get()
            ->keyBy('coa_id');

        $this->items = [];

        foreach ($coas as $coa) {
            $refDebit  = (float) ($consolidatedRows->get($coa->id)->debit ?? 0);
            $refCredit = (float) ($consolidatedRows->get($coa->id)->credit ?? 0);

            $debit  = (float) ($entityRows->get($coa->id)->debit ?? 0);
            $credit = (float) ($entityRows->get($coa->id)->credit ?? 0);

            $this->items[$coa->id] = [
                'coa_id'     => $coa->id,
                'code'       => $coa->code,
                'name'       => $coa->name,
                'group_id'   => $coa->group_coa_id,
                'group_name' => $coa->group_name,
                'ref_debit'  => $refDebit,
                'ref_credit' => $refCredit,
                'debit'      => $debit,
                'credit'     => $credit,
            ];
        }
    }

    public function changeEntity(string $entity): void
    {
        $this->entity = strtoupper($entity);
        if (!in_array($this->entity, ['PT', 'KKP'], true)) {
            $this->entity = 'PT';
        }
        $this->loadData();
    }

    public function changePeriod(int $year, int $month): void
    {
        $this->year  = $year;
        $this->month = $month;
        $this->loadData();
    }

    public function salinDariKonsolidasi(): void
    {
        foreach ($this->items as $coaId => &$item) {
            $item['debit']  = $item['ref_debit'];
            $item['credit'] = $item['ref_credit'];
        }
        unset($item);

        Notification::make()
            ->info()
            ->title('Nilai disalin dari Saldo Konsolidasi')
            ->body('Seluruh nilai Saldo Konsolidasi telah disalin ke form. Silakan sesuaikan pembagian untuk entitas ' . $this->entity . '.')
            ->send();
    }

    public function kosongkanSemua(): void
    {
        foreach ($this->items as $coaId => &$item) {
            $item['debit']  = 0;
            $item['credit'] = 0;
        }
        unset($item);

        Notification::make()
            ->warning()
            ->title('Nominal Dikosongkan')
            ->body('Seluruh nominal input untuk ' . $this->entity . ' telah di-reset ke 0.')
            ->send();
    }

    public function getTotalDebitProperty(): float
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += (float) ($item['debit'] ?? 0);
        }
        return $total;
    }

    public function getTotalCreditProperty(): float
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += (float) ($item['credit'] ?? 0);
        }
        return $total;
    }

    public function getSelisihProperty(): float
    {
        return $this->totalDebit - $this->totalCredit;
    }

    public function getCutoffDateLabel(): string
    {
        $prevCarbon = Carbon::create($this->year, $this->month, 1)->subMonth()->endOfMonth();
        return $prevCarbon->translatedFormat('d F Y');
    }

    public function save(): void
    {
        $selisih = abs($this->selisih);
        if ($selisih > 0.01) {
            Notification::make()
                ->danger()
                ->title('Laporan Belum Seimbang (Tidak Balance)!')
                ->body('Total Debit dan Kredit harus bernilai sama. Saat ini terdapat selisih sebesar Rp ' . number_format($selisih, 0, ',', '.') . '. Harap seimbangkan neraca sebelum menyimpan.')
                ->persistent()
                ->send();
            return;
        }

        $prevCarbon = Carbon::create($this->year, $this->month, 1)->subMonth()->endOfMonth();
        $targetDate = $prevCarbon->toDateString();
        $prevYear   = $prevCarbon->year;
        $prevMonth  = $prevCarbon->month;

        DB::transaction(function () use ($prevYear, $prevMonth, $targetDate) {
            // Hapus saldo neraca bulanan sebelumnya untuk entitas ini pada tanggal cut-off
            JournalBookReport::where('journal_book_id', 3)
                ->whereYear('transaction_date', $prevYear)
                ->whereMonth('transaction_date', $prevMonth)
                ->whereRaw('UPPER(kepemilikan) = ?', [$this->entity])
                ->forceDelete();

            $inserted = 0;
            foreach ($this->items as $item) {
                $debit  = round((float) ($item['debit'] ?? 0), 2);
                $credit = round((float) ($item['credit'] ?? 0), 2);

                if ($debit > 0 || $credit > 0) {
                    JournalBookReport::create([
                        'description'      => $item['code'] . ' - ' . $item['name'],
                        'journal_book_id'  => 3,
                        'debit_amount'     => $debit,
                        'credit_amount'    => $credit,
                        'coa_id'           => $item['coa_id'],
                        'transaction_date' => $targetDate,
                        'kepemilikan'      => $this->entity,
                    ]);
                    $inserted++;
                }
            }
        });

        Notification::make()
            ->success()
            ->title('Saldo Awal ' . $this->entity . ' Berhasil Disimpan!')
            ->body('Data Saldo Awal per ' . $this->getCutoffDateLabel() . ' telah tersimpan dan siap digunakan pada Neraca Lajur.')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali ke Neraca Lajur')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn() => CashReportResource::getUrl('neraca-lajur-piutang', [
                    'year'   => $this->year,
                    'month'  => $this->month,
                    'entity' => strtolower($this->entity),
                ])),
        ];
    }
}

