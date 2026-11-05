<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\PiutangPerCategoryMou;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class PiutangCategoryMouOverviewWidget extends BaseWidget
{
    protected static ?string $widgetId = 'piutang-category-mou-overview';

    public array $tableFilters = [];

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $this->tableFilters = request('tableFilters', []);
    }

    #[On('filament.table.filtered')]
    public function handleTableFiltered(?array $data): void
    {
        $this->tableFilters = $data ?? [];
    }

    protected function getStats(): array
    {
        $year = $this->tableFilters['year']['value'] ?? request('tableFilters.year.value', (string) date('Y'));
        $entity = $this->tableFilters['entity']['value'] ?? request('tableFilters.entity.value', 'all');

        $query = PiutangPerCategoryMou::getBaseCategoryQuery($year, $entity);
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
            Stat::make('Total Tagihan (Invoice)', 'Rp ' . number_format($totalInvoice, 0, ',', '.'))
                ->description('Akumulasi seluruh kategori')
                ->icon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make('Total Pembayaran Masuk', 'Rp ' . number_format($totalPembayaran, 0, ',', '.'))
                ->description($rasio . '% terbayar')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total Sisa Piutang', 'Rp ' . number_format($totalPiutang, 0, ',', '.'))
                ->description($countKategoriPiutang . ' kategori ada piutang')
                ->icon('heroicon-o-clock')
                ->color($totalPiutang > 0 ? 'warning' : 'success'),

            Stat::make('Piutang Terbesar', $topCategory)
                ->description('Rp ' . number_format($topAmount, 0, ',', '.'))
                ->icon('heroicon-o-trophy')
                ->color('danger'),
        ];
    }
}

