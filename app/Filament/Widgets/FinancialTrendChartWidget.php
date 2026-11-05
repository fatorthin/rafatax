<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\FinancialAnalyticsService;
use Filament\Widgets\ChartWidget;

class FinancialTrendChartWidget extends ChartWidget
{
    protected static ?string $widgetId = 'financial-trend-chart';

    protected static ?string $heading = 'Grafik Tren Finansial 12 Bulan';

    protected static ?string $maxHeight = '350px';

    public ?int $year = null;
    public string $entity = 'all';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user && $user->hasRole('admin');
    }

    public function getDescription(): ?string
    {
        $y = $this->year ?? (int) date('Y');
        return "Pergerakan Pendapatan, Beban, dan Laba Bersih Tahun {$y}";
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $y = $this->year ?? (int) date('Y');

        $analytics = app(FinancialAnalyticsService::class);
        $trend = $analytics->get12MonthTrend($y, $this->entity);

        return [
            'datasets' => [
                [
                    'type' => 'line',
                    'label' => 'Laba Bersih',
                    'data' => $trend['laba'],
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderWidth' => 3,
                    'fill' => false,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'type' => 'bar',
                    'label' => 'Pendapatan',
                    'data' => $trend['pendapatan'],
                    'backgroundColor' => '#f59e0b',
                    'borderRadius' => 4,
                    'yAxisID' => 'y',
                ],
                [
                    'type' => 'bar',
                    'label' => 'Beban',
                    'data' => $trend['beban'],
                    'backgroundColor' => '#f43f5e',
                    'borderRadius' => 4,
                    'yAxisID' => 'y',
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'categoryPercentage' => 0.7,
            'barPercentage' => 0.85,
            'maxBarThickness' => 32,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
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
