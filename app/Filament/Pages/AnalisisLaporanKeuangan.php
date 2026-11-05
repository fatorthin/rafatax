<?php

namespace App\Filament\Pages;

use App\Services\FinancialAnalyticsService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalisisLaporanKeuangan extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.analisis-laporan-keuangan';

    protected static ?string $slug = 'analisis-laporan-keuangan';

    protected static ?string $navigationGroup = 'Analisis Eksekutif';

    protected static ?string $navigationLabel = 'Analisis Keuangan Bulanan';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Analisis Laporan Keuangan Bulanan';

    public int $primaryMonth;
    public int $primaryYear;
    public int $compareMonth;
    public int $compareYear;
    public string $entity = 'all';
    public string $activeTab = 'comparison'; // 'comparison' or 'trend'

    public static function canAccess(array $parameters = []): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Khusus untuk role admin
        return $user->hasRole('admin');
    }

    public function mount(): void
    {
        $now = now();
        $this->primaryMonth = (int) request('primary_month', $now->month);
        $this->primaryYear = (int) request('primary_year', $now->year);

        // Default: Month-over-Month (MoM) -> 1 bulan sebelum bulan utama
        $prev = Carbon::create($this->primaryYear, $this->primaryMonth, 1)->subMonth();
        $this->compareMonth = (int) request('compare_month', $prev->month);
        $this->compareYear = (int) request('compare_year', $prev->year);

        $this->entity = request('entity', 'all');
        $this->activeTab = request('tab', 'comparison');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quick_mom')
                ->label('Bandingkan MoM')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->tooltip('Set otomatis: Bulan Berjalan vs 1 Bulan Sebelumnya')
                ->action(function (): void {
                    $now = now();
                    $prev = Carbon::create($now->year, $now->month, 1)->subMonth();

                    $this->primaryMonth = $now->month;
                    $this->primaryYear = $now->year;
                    $this->compareMonth = $prev->month;
                    $this->compareYear = $prev->year;

                    $this->redirect(static::getUrl([
                        'primary_month' => $this->primaryMonth,
                        'primary_year' => $this->primaryYear,
                        'compare_month' => $this->compareMonth,
                        'compare_year' => $this->compareYear,
                        'entity' => $this->entity,
                        'tab' => $this->activeTab,
                    ]));
                }),

            Action::make('quick_yoy')
                ->label('Bandingkan YoY')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->tooltip('Set otomatis: Bulan Berjalan vs Bulan Sama Tahun Lalu')
                ->action(function (): void {
                    $now = now();
                    $this->primaryMonth = $now->month;
                    $this->primaryYear = $now->year;
                    $this->compareMonth = $now->month;
                    $this->compareYear = $now->year - 1;

                    $this->redirect(static::getUrl([
                        'primary_month' => $this->primaryMonth,
                        'primary_year' => $this->primaryYear,
                        'compare_month' => $this->compareMonth,
                        'compare_year' => $this->compareYear,
                        'entity' => $this->entity,
                        'tab' => $this->activeTab,
                    ]));
                }),

            Action::make('filter')
                ->label('Filter Kustom')
                ->icon('heroicon-o-funnel')
                ->color('info')
                ->form([
                    Grid::make(2)->schema([
                        Select::make('primary_month')
                            ->label('Bulan Utama')
                            ->options(FinancialAnalyticsService::getMonthNames())
                            ->default($this->primaryMonth)
                            ->required(),
                        Select::make('primary_year')
                            ->label('Tahun Utama')
                            ->options($this->getYearOptions())
                            ->default($this->primaryYear)
                            ->required(),
                    ]),
                    Grid::make(2)->schema([
                        Select::make('compare_month')
                            ->label('Bulan Pembanding')
                            ->options(FinancialAnalyticsService::getMonthNames())
                            ->default($this->compareMonth)
                            ->required(),
                        Select::make('compare_year')
                            ->label('Tahun Pembanding')
                            ->options($this->getYearOptions())
                            ->default($this->compareYear)
                            ->required(),
                    ]),
                    Select::make('entity')
                        ->label('Entitas Bisnis')
                        ->options([
                            'all' => 'Semua (Konsolidasi)',
                            'kkp' => 'KKP',
                            'pt'  => 'PT',
                        ])
                        ->default($this->entity)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->primaryMonth = (int) $data['primary_month'];
                    $this->primaryYear = (int) $data['primary_year'];
                    $this->compareMonth = (int) $data['compare_month'];
                    $this->compareYear = (int) $data['compare_year'];
                    $this->entity = $data['entity'] ?? 'all';

                    $this->redirect(static::getUrl([
                        'primary_month' => $this->primaryMonth,
                        'primary_year' => $this->primaryYear,
                        'compare_month' => $this->compareMonth,
                        'compare_year' => $this->compareYear,
                        'entity' => $this->entity,
                        'tab' => $this->activeTab,
                    ]));
                }),

            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn(): StreamedResponse => app(FinancialAnalyticsService::class)->exportComparisonToExcel(
                    $this->primaryMonth,
                    $this->primaryYear,
                    $this->compareMonth,
                    $this->compareYear,
                    $this->entity
                )),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getComparisonData(): array
    {
        return app(FinancialAnalyticsService::class)->getComparison(
            $this->primaryMonth,
            $this->primaryYear,
            $this->compareMonth,
            $this->compareYear,
            $this->entity
        );
    }

    public function getTrendData(): array
    {
        return app(FinancialAnalyticsService::class)->get12MonthTrend(
            $this->primaryYear,
            $this->entity
        );
    }

    protected function getYearOptions(): array
    {
        $years = [];
        $current = now()->year;
        for ($y = $current - 5; $y <= $current + 1; $y++) {
            $years[$y] = (string) $y;
        }
        return $years;
    }
}

