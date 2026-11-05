<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\FinancialAnalyticsService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialKpiOverviewWidget extends BaseWidget
{
    protected static ?string $widgetId = 'financial-kpi-overview';

    public ?int $primaryMonth = null;

    public ?int $primaryYear = null;

    public ?int $compareMonth = null;

    public ?int $compareYear = null;

    public string $entity = 'all';

    protected function getColumns(): int
    {
        return 4;
    }

    /**
     * Widget khusus untuk role admin (tidak didaftarkan di Dashboard Panel)
     */
    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user && $user->hasRole('admin');
    }

    public function mount(): void
    {
        $this->primaryMonth = $this->primaryMonth ?? (int) request('primary_month', now()->month);
        $this->primaryYear = $this->primaryYear ?? (int) request('primary_year', now()->year);

        $prev = Carbon::create($this->primaryYear, $this->primaryMonth, 1)->subMonth();
        $this->compareMonth = $this->compareMonth ?? (int) request('compare_month', $prev->month);
        $this->compareYear = $this->compareYear ?? (int) request('compare_year', $prev->year);

        $this->entity = $this->entity ?? request('entity', 'all');
    }

    protected function getStats(): array
    {
        $service = app(FinancialAnalyticsService::class);
        $comparison = $service->getComparison(
            $this->primaryMonth,
            $this->primaryYear,
            $this->compareMonth,
            $this->compareYear,
            $this->entity
        );

        $kpi = $comparison['kpi'];
        $p2Label = $comparison['period2']['period_label'];

        // 1. Total Pendapatan
        $pendNominal = $kpi['pendapatan']['current'];
        $pendDiffNom = $kpi['pendapatan']['diff_nominal'];
        $pendDiffPct = $kpi['pendapatan']['diff_percent'];
        $pendFavorable = $kpi['pendapatan']['is_favorable'];

        // 2. Total Beban
        $bebanNominal = $kpi['beban']['current'];
        $bebanDiffNom = $kpi['beban']['diff_nominal'];
        $bebanDiffPct = $kpi['beban']['diff_percent'];
        $bebanFavorable = $kpi['beban']['is_favorable'];

        // 3. Laba Bersih
        $labaNominal = $kpi['laba_bersih']['current'];
        $labaDiffNom = $kpi['laba_bersih']['diff_nominal'];
        $labaDiffPct = $kpi['laba_bersih']['diff_percent'];
        $labaFavorable = $kpi['laba_bersih']['is_favorable'];

        // 4. Net Profit Margin
        $marginNominal = $kpi['margin']['current'];
        $marginDiff = $kpi['margin']['diff_margin'];
        $marginFavorable = $kpi['margin']['is_favorable'];

        return [
            Stat::make('Total Pendapatan', 'Rp '.number_format($pendNominal, 0, ',', '.'))
                ->description(($pendDiffPct >= 0 ? '+' : '').number_format($pendDiffPct, 1).'% vs '.$p2Label.' ('.($pendDiffNom >= 0 ? '+' : '').number_format($pendDiffNom, 0, ',', '.').')')
                ->descriptionIcon($pendDiffNom >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($pendFavorable ? 'success' : 'danger'),

            Stat::make('Total Beban Operasional', 'Rp '.number_format($bebanNominal, 0, ',', '.'))
                ->description(($bebanDiffPct >= 0 ? '+' : '').number_format($bebanDiffPct, 1).'% vs '.$p2Label.' ('.($bebanDiffNom >= 0 ? '+' : '').number_format($bebanDiffNom, 0, ',', '.').')')
                ->descriptionIcon($bebanDiffNom <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($bebanFavorable ? 'success' : 'danger'),

            Stat::make('Laba / (Rugi) Bersih', 'Rp '.number_format($labaNominal, 0, ',', '.'))
                ->description(($labaDiffPct >= 0 ? '+' : '').number_format($labaDiffPct, 1).'% vs '.$p2Label.' ('.($labaDiffNom >= 0 ? '+' : '').number_format($labaDiffNom, 0, ',', '.').')')
                ->descriptionIcon($labaDiffNom >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($labaFavorable ? 'success' : 'danger'),

            Stat::make('Net Profit Margin (NPM)', number_format($marginNominal, 1).'%')
                ->description(($marginDiff >= 0 ? '+' : '').number_format($marginDiff, 1).' %pt vs '.$p2Label)
                ->descriptionIcon($marginDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($marginFavorable ? 'success' : 'danger'),
        ];
    }
}
