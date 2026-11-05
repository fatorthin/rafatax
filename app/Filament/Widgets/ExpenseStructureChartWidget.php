<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\FinancialAnalyticsService;
use Filament\Widgets\ChartWidget;

class ExpenseStructureChartWidget extends ChartWidget
{
    protected static ?string $widgetId = 'expense-structure-chart';

    protected static ?string $heading = 'Struktur Beban (Cost Drivers)';

    protected static ?string $maxHeight = '200px';

    protected static string $view = 'filament.widgets.expense-structure-chart-widget';

    public ?int $primaryMonth = null;
    public ?int $primaryYear = null;
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

        return "Komposisi Beban Terbesar {$p1}";
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function getExpenseSummary(): array
    {
        $pMonth = $this->primaryMonth ?? (int) date('n');
        $pYear = $this->primaryYear ?? (int) date('Y');

        $analytics = app(FinancialAnalyticsService::class);
        return $analytics->getMonthlyData($pMonth, $pYear, $this->entity);
    }

    protected function getData(): array
    {
        $summary = $this->getExpenseSummary();
        $breakdown = $summary['expenseBreakdown'] ?? [];

        $labels = array_column($breakdown, 'label');
        $data = array_map(fn ($i) => (float) $i['amount'], $breakdown);

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => [
                        '#f43f5e', // Rose
                        '#0ea5e9', // Sky
                        '#10b981', // Emerald
                        '#8b5cf6', // Violet
                        '#f59e0b', // Amber
                        '#94a3b8', // Slate
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '70%',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }
}
