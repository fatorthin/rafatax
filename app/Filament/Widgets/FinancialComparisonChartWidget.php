<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\FinancialAnalyticsService;
use Filament\Widgets\ChartWidget;

class FinancialComparisonChartWidget extends ChartWidget
{
    protected static ?string $widgetId = 'financial-comparison-chart';

    protected static ?string $heading = 'Perbandingan Finansial Periode';

    protected static ?string $maxHeight = '300px';

    public ?int $primaryMonth = null;
    public ?int $primaryYear = null;
    public ?int $compareMonth = null;
    public ?int $compareYear = null;
    public string $entity = 'all';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user && $user->hasRole('admin');
    }

    public function getDescription(): ?string
    {
        $months = FinancialAnalyticsService::getMonthNames();
        $p1 = ($months[$this->primaryMonth ?? date('n')] ?? '') . ' ' . ($this->primaryYear ?? date('Y'));
        $p2 = ($months[$this->compareMonth ?? (date('n') - 1 ?: 12)] ?? '') . ' ' . ($this->compareYear ?? date('Y'));

        return "{$p1} vs {$p2}";
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $pMonth = $this->primaryMonth ?? (int) date('n');
        $pYear = $this->primaryYear ?? (int) date('Y');
        $cMonth = $this->compareMonth ?? (int) ($pMonth === 1 ? 12 : $pMonth - 1);
        $cYear = $this->compareYear ?? (int) ($pMonth === 1 ? $pYear - 1 : $pYear);

        $analytics = app(FinancialAnalyticsService::class);
        $analysis = $analytics->getComparison($pMonth, $pYear, $cMonth, $cYear, $this->entity);

        $kpi = $analysis['kpi'];
        $p1Label = $analysis['period1']['period_label'];
        $p2Label = $analysis['period2']['period_label'];

        return [
            'datasets' => [
                [
                    'label' => $p1Label,
                    'data' => [
                        round($kpi['pendapatan']['current']),
                        round($kpi['beban']['current']),
                        round($kpi['laba_bersih']['current']),
                    ],
                    'backgroundColor' => '#f59e0b',
                    'borderRadius' => 6,
                ],
                [
                    'label' => $p2Label,
                    'data' => [
                        round($kpi['pendapatan']['previous']),
                        round($kpi['beban']['previous']),
                        round($kpi['laba_bersih']['previous']),
                    ],
                    'backgroundColor' => '#64748b',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => ['Total Pendapatan', 'Total Beban', 'Laba Bersih'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'categoryPercentage' => 0.55,
            'barPercentage' => 0.8,
            'maxBarThickness' => 44,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                ],
                'y' => [
                    'ticks' => [
                        'callback' => '(function(val) { return "Rp " + (val / 1000000).toFixed(0) + " Jt"; })',
                    ],
                ],
            ],
        ];
    }
}
