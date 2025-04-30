<?php

namespace App\Providers\Filament;

use App\Filament\Resources\CashReferenceResource;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\CoaResource;
use App\Filament\Resources\IncomeStatementResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\MouResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\InvoiceStats;
use App\Filament\Widgets\MouStats;
use App\Filament\Widgets\ClientStats;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession; 
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->sidebarCollapsibleOnDesktop()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
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
                    ->label('System')
                    ->icon('heroicon-o-cog')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Master Data')
                    ->icon('heroicon-o-document-text')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Transactions')
                    ->icon('heroicon-o-currency-dollar')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Reports')
                    ->icon('heroicon-o-chart-bar')
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
            ])
            ->maxContentWidth('full');
    }
}
