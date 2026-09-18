<?php

namespace App\Filament\App\Pages;

use App\Filament\Resources\CashReportResource\Pages\NeracaLajurPiutang as BaseNeracaLajurPiutang;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Route;

class NeracaLajurPiutang extends BaseNeracaLajurPiutang
{
    protected static bool $isDiscovered = true;

    protected static ?string $title = 'Neraca Lajur (Konsep Piutang)';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Neraca Lajur (Piutang)';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 3;

    public static function canAccess(array $parameters = []): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('cash-manager') || $user->hasRole('admin');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return static::canAccess($parameters);
    }

    public static function getRouteName(?string $panel = null): string
    {
        $panel = $panel ? Filament::getPanel($panel) : Filament::getCurrentPanel();

        $routeName = 'pages.' . static::getRelativeRouteName();
        $routeName = static::prependClusterRouteBaseName($routeName);

        return $panel->generateRouteName($routeName);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        if (blank($panel) || Filament::getPanel($panel)->hasTenancy()) {
            $parameters['tenant'] ??= ($tenant ?? Filament::getTenant());
        }

        return route(static::getRouteName($panel), $parameters, $isAbsolute);
    }

    /**
     * @param  array<string, mixed>  $urlParameters
     */
    public static function getNavigationItems(array $urlParameters = []): array
    {
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getNavigationItemActiveRoutePattern()))
                ->sort(static::getNavigationSort())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->url(static::getUrl($urlParameters)),
        ];
    }

    public static function getNavigationItemActiveRoutePattern(): string
    {
        return static::getRouteName();
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getUrl() => static::getTitle(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filter Periode')
                ->icon('heroicon-o-funnel')
                ->form([
                    Select::make('period')
                        ->label('Periode')
                        ->options([
                            '2026-1'  => 'Januari 2026',
                            '2026-2'  => 'Februari 2026',
                            '2026-3'  => 'Maret 2026',
                            '2026-4'  => 'April 2026',
                            '2026-5'  => 'Mei 2026',
                            '2026-6'  => 'Juni 2026',
                            '2026-7'  => 'Juli 2026',
                            '2026-8'  => 'Agustus 2026',
                            '2026-9'  => 'September 2026',
                            '2026-10' => 'Oktober 2026',
                            '2026-11' => 'November 2026',
                            '2026-12' => 'Desember 2026',
                        ])
                        ->default($this->year . '-' . $this->month)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $parts = explode('-', $data['period']);
                    $this->year  = (int) $parts[0];
                    $this->month = (int) $parts[1];
                    $this->redirect(static::getUrl([
                        'month' => $this->month,
                        'year'  => $this->year,
                    ]));
                }),
            Action::make('viewOld')
                ->label('Lihat Neraca Lajur (Lama)')
                ->icon('heroicon-o-arrow-left-circle')
                ->color('gray')
                ->url(function () {
                    if (Route::has('filament.app.pages.neraca-lajur-bulanan')) {
                        return route('filament.app.pages.neraca-lajur-bulanan', [
                            'month' => $this->month,
                            'year'  => $this->year,
                        ]);
                    }
                    return null;
                }),
            Action::make('exportDetailJP')
                ->label('Export Detail Jurnal Pendapatan')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('info')
                ->url(fn() => url('/neraca-lajur-piutang/export-detail-jp?month=' . $this->month . '&year=' . $this->year))
                ->openUrlInNewTab(false),
            Action::make('export')
                ->label('Export Neraca Lajur')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn() => url('/neraca-lajur-piutang/export?month=' . $this->month . '&year=' . $this->year))
                ->openUrlInNewTab(false),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(function () {
                    if (Route::has('filament.app.resources.cash-reports.index')) {
                        return route('filament.app.resources.cash-reports.index');
                    }
                    return route('filament.app.pages.dashboard');
                }),
        ];
    }
}
