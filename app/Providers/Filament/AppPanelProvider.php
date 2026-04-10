<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Http\Middleware\RedirectAdminToAdminPanel;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->favicon(asset('images/favicon.png'))
            ->id('app')
            ->brandName('Rafatax App')
            ->authGuard('web')
            ->userMenuItems([
                MenuItem::make()
                    ->label('Profile')
                    ->icon('heroicon-o-user')
                    ->url('/app/profile'),
            ])
            ->path('app')
            ->colors([
                'primary' => Color::Cyan,
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\App\Pages\Profile::class,
                \App\Filament\App\Pages\NeracaLajurBulanan::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
                RedirectAdminToAdminPanel::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->emailVerification()
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn(): string => '<style>
                    /* Globally limit table height to allow vertical scrolling */
                    .fi-ta-content { max-height: 75vh; overflow: auto !important; }
                    /* Make all table headers sticky */
                    .fi-ta-table thead { position: sticky; top: 0; z-index: 20; }
                    /* Ensure overlapping body content is hidden behind header */
                    .fi-ta-table thead th { background-color: #f9fafb; }
                    .dark .fi-ta-table thead th { background-color: #18181b; }
                </style>'
            )
            ->maxContentWidth('full');
    }
}
