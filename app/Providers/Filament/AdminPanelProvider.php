<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use App\Filament\Widgets\MouStats;
use Filament\Support\Colors\Color;
use App\Filament\Widgets\ClientStats;
use App\Filament\Widgets\InvoiceStats;
use App\Filament\Resources\CoaResource;
use App\Filament\Resources\MouResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use Filament\Navigation\NavigationGroup;
use App\Filament\Resources\StaffResource;
use App\Filament\Resources\ClientResource;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Resources\InvoiceResource;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Resources\CashReferenceResource;
use Filament\Http\Middleware\AuthenticateSession;
use App\Filament\Resources\IncomeStatementResource;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->favicon(asset('images/favicon.png'))
            ->sidebarCollapsibleOnDesktop()
            ->id('admin')
            ->path('admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Dashboard')
                    ->icon('heroicon-o-home')
                    ->url('/app')

            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                InvoiceStats::class,
                MouStats::class,
                ClientStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Bagian Keuangan')
                    ->icon('heroicon-o-currency-dollar')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Bagian HRD')
                    ->icon('heroicon-o-users')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Referensi')
                    ->icon('heroicon-o-rectangle-stack')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('System')
                    ->icon('heroicon-o-cog')
                    ->collapsed(),
            ])
            ->resources([
                UserResource::class,
                RoleResource::class,
                ClientResource::class,
                CoaResource::class,
                CashReferenceResource::class,
                MouResource::class,
                InvoiceResource::class,
                IncomeStatementResource::class,
                StaffResource::class,
            ])
            ->maxContentWidth('full');
    }
}
