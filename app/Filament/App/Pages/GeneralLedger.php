<?php

namespace App\Filament\App\Pages;

use App\Filament\Resources\CashReportResource\Pages\GeneralLedger as BaseGeneralLedger;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;

class GeneralLedger extends BaseGeneralLedger
{
    protected static bool $isDiscovered = true;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Buku Besar';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 4;

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

        return $user->hasPermission('cash-report.view') || $user->hasPermission('cash-reports.view');
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
            NavigationItem::make('Buku Besar (PT)')
                ->group('Laporan Keuangan PT')
                ->icon('heroicon-o-book-open')
                ->url(static::getUrl(['entity' => 'pt']))
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteName()) && strtolower(request('entity', 'all')) === 'pt')
                ->sort(4),
            NavigationItem::make('Buku Besar (KKP)')
                ->group('Laporan Keuangan KKP')
                ->icon('heroicon-o-book-open')
                ->url(static::getUrl(['entity' => 'kkp']))
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteName()) && strtolower(request('entity', 'all')) === 'kkp')
                ->sort(4),
            NavigationItem::make('Buku Besar Konsolidasi')
                ->group('Laporan Konsolidasi')
                ->icon('heroicon-o-book-open')
                ->url(static::getUrl(['entity' => 'all']))
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteName()) && strtolower(request('entity', 'all')) === 'all')
                ->sort(4),
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
}
