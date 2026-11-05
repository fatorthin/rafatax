<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MouResource;
use App\Models\CategoryMou;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PiutangPerCategoryMou extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static string $view = 'filament.pages.piutang-per-category-mou';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Piutang per Kategori MoU';

    protected static ?string $title = 'Piutang per Kategori MoU';

    protected static ?int $navigationSort = 6;

    public function getHeaderWidgetsColumns(): int | string | array
    {
        return 1;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\PiutangCategoryMouOverviewWidget::class,
        ];
    }

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

        return $user->hasRole('cash-manager')
            || $user->hasPermission('invoice.view')
            || $user->hasPermission('invoices.view')
            || $user->hasPermission('mou.view');
    }

    /**
     * Base query for Category MoU with aggregated invoice, payment, and piutang.
     */
    public static function getBaseCategoryQuery(?string $year = null, ?string $entity = null): Builder
    {
        $year = $year ?? (string) date('Y');

        $yearCondition = '';
        $mouYearCondition = '';
        if ($year !== 'all') {
            $yearInt = (int) $year;
            $yearCondition = "AND YEAR(i.invoice_date) = {$yearInt}";
            $mouYearCondition = "AND (YEAR(created_at) = {$yearInt} OR start_date >= '{$yearInt}-01-01')";
        }

        $entityCondition = '';
        $mouEntityCondition = '';
        if ($entity === 'pt' || $entity === 'kkp') {
            $entityCondition = "AND m.type = '{$entity}'";
            $mouEntityCondition = "AND type = '{$entity}'";
        }

        $invSql = "
            SELECT 
                m.category_mou_id,
                COUNT(DISTINCT i.id) as jumlah_invoice,
                COALESCE(SUM(cli.amount), 0) as total_invoice,
                COALESCE(SUM(CASE WHEN i.invoice_status = 'paid' THEN cli.amount ELSE 0 END), 0) as total_pembayaran,
                COALESCE(SUM(CASE WHEN i.invoice_status != 'paid' THEN cli.amount ELSE 0 END), 0) as total_piutang
            FROM cost_list_invoices cli
            JOIN invoices i ON cli.invoice_id = i.id
            JOIN mous m ON i.mou_id = m.id
            WHERE cli.deleted_at IS NULL
              AND i.deleted_at IS NULL
              AND m.deleted_at IS NULL
              AND i.memo_id IS NULL
              AND (m.mou_piutang_lama = 0 OR m.mou_piutang_lama IS NULL)
              {$yearCondition}
              {$entityCondition}
            GROUP BY m.category_mou_id
        ";

        $mouSql = "
            SELECT 
                category_mou_id,
                COUNT(DISTINCT id) as jumlah_mou
            FROM mous
            WHERE deleted_at IS NULL
              AND (mou_piutang_lama = 0 OR mou_piutang_lama IS NULL)
              {$mouYearCondition}
              {$mouEntityCondition}
            GROUP BY category_mou_id
        ";

        return CategoryMou::query()
            ->select('category_mous.*')
            ->selectRaw('COALESCE(m_data.jumlah_mou, 0) as jumlah_mou')
            ->selectRaw('COALESCE(inv_data.jumlah_invoice, 0) as jumlah_invoice')
            ->selectRaw('COALESCE(inv_data.total_invoice, 0) as total_invoice')
            ->selectRaw('COALESCE(inv_data.total_pembayaran, 0) as total_pembayaran')
            ->selectRaw('COALESCE(inv_data.total_piutang, 0) as total_piutang')
            ->leftJoin(DB::raw("({$mouSql}) as m_data"), 'category_mous.id', '=', 'm_data.category_mou_id')
            ->leftJoin(DB::raw("({$invSql}) as inv_data"), 'category_mous.id', '=', 'inv_data.category_mou_id');
    }

    public function table(Table $table): Table
    {
        $currentYear = (string) date('Y');

        return $table
            ->query(static::getBaseCategoryQuery())
            ->defaultSort('inv_data.total_piutang', 'desc')
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width('50px'),

                TextColumn::make('name')
                    ->label('Kategori MoU')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (CategoryMou $record): string => "ID: {$record->id}"),

                TextColumn::make('jumlah_mou')
                    ->label('Jumlah MoU')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('jumlah_invoice')
                    ->label('Jumlah Invoice')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('total_invoice')
                    ->label('Total Tagihan (Invoice)')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('total_pembayaran')
                    ->label('Total Terbayar')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->color('success'),

                TextColumn::make('total_piutang')
                    ->label('Sisa Piutang')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($state) => (float) $state > 0 ? 'amber' : 'gray'),

                TextColumn::make('rasio_pelunasan')
                    ->label('Rasio Lunas')
                    ->alignCenter()
                    ->state(function (CategoryMou $record): string {
                        $inv = (float) $record->total_invoice;
                        $pay = (float) $record->total_pembayaran;
                        if ($inv <= 0) {
                            return '100%';
                        }
                        return round(($pay / $inv) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color(function (CategoryMou $record): string {
                        $inv = (float) $record->total_invoice;
                        $pay = (float) $record->total_pembayaran;
                        if ($inv <= 0 || $pay >= $inv) {
                            return 'success';
                        }
                        return 'warning';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Periode Tahun')
                    ->options([
                        $currentYear => "{$currentYear} (Tahun Ini)",
                        '2025' => '2025',
                        '2024' => '2024',
                        'all' => 'Semua Waktu',
                    ])
                    ->default($currentYear)
                    ->query(function (Builder $query, array $data) {
                        // Handled via getBaseCategoryQuery rewrite when filter state is applied
                        $year = !empty($data['value']) ? $data['value'] : (string) date('Y');
                        $entity = request()->input('tableFilters.entity.value', 'all');

                        // Rebind joins with new filter
                        $newBase = static::getBaseCategoryQuery($year, $entity);
                        $query->from($newBase->getQuery()->from)
                            ->select($newBase->getQuery()->columns)
                            ->setBindings($newBase->getQuery()->getRawBindings()['select'] ?? [], 'select');
                    }),

                Tables\Filters\SelectFilter::make('entity')
                    ->label('Tipe MoU')
                    ->options([
                        'all' => 'Semua (PT & KKP)',
                        'pt' => 'PT Saja',
                        'kkp' => 'KKP Saja',
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data) {
                        $entity = !empty($data['value']) ? $data['value'] : 'all';
                        $year = request()->input('tableFilters.year.value', (string) date('Y'));

                        $newBase = static::getBaseCategoryQuery($year, $entity);
                        $query->from($newBase->getQuery()->from)
                            ->select($newBase->getQuery()->columns);
                    }),

                Tables\Filters\Filter::make('piutang_aktif')
                    ->label('Hanya Kategori Berpiutang (> 0)')
                    ->query(fn (Builder $query) => $query->having('total_piutang', '>', 0))
                    ->default(false),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel Rekap')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(function () {
                        $filters = request('tableFilters', []);
                        $year = $filters['year']['value'] ?? date('Y');
                        $entity = $filters['entity']['value'] ?? 'all';

                        return route('piutang-category-mou.export-summary', [
                            'year' => $year,
                            'entity' => $entity,
                        ]);
                    })
                    ->openUrlInNewTab(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(function (CategoryMou $record): string {
                        $filters = request('tableFilters', []);
                        $year = $filters['year']['value'] ?? date('Y');
                        $entity = $filters['entity']['value'] ?? 'all';

                        return route('piutang-category-mou.detail', [
                            'id' => $record->id,
                            'year' => $year,
                            'entity' => $entity,
                        ]);
                    }),

                Tables\Actions\Action::make('view_mou')
                    ->label('Buka MoU')
                    ->icon('heroicon-o-list-bullet')
                    ->color('gray')
                    ->url(fn (CategoryMou $record): string => MouResource::getUrl('index', [
                        'tableFilters' => [
                            'category_mou_id' => [
                                'value' => $record->id,
                            ],
                        ],
                    ]))
                    ->openUrlInNewTab(true),
            ])
            ->paginated(false);
    }

    /**
     * Hitung ringkasan statistik untuk kartu metrik atas.
     */
    public function getStats(): array
    {
        $filters = request('tableFilters', []);
        $year = $filters['year']['value'] ?? (string) date('Y');
        $entity = $filters['entity']['value'] ?? 'all';

        $query = static::getBaseCategoryQuery($year, $entity);
        $records = $query->get();

        $totalInvoice = (float) $records->sum('total_invoice');
        $totalPembayaran = (float) $records->sum('total_pembayaran');
        $totalPiutang = (float) $records->sum('total_piutang');
        $countKategoriPiutang = $records->where('total_piutang', '>', 0)->count();

        $top = $records->sortByDesc('total_piutang')->first();
        $topCategory = ($top && (float) $top->total_piutang > 0) ? $top->name : '-';
        $topAmount = ($top && (float) $top->total_piutang > 0) ? (float) $top->total_piutang : 0;

        $rasio = $totalInvoice > 0 ? round(($totalPembayaran / $totalInvoice) * 100, 1) : 100;

        return [
            'total_invoice'          => $totalInvoice,
            'total_pembayaran'       => $totalPembayaran,
            'total_piutang'          => $totalPiutang,
            'rasio_pelunasan'        => $rasio,
            'count_kategori_piutang' => $countKategoriPiutang,
            'top_piutang_category'   => $topCategory,
            'top_piutang_amount'     => $topAmount,
        ];
    }
}
