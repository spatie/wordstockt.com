<?php

namespace App\Support\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('WordStockt')
            ->colors([
                // One hue (the site's brand blue). Filament puts white button text on 600 with a 500 hover, so both stay dark enough for 4.5:1.
                'primary' => [
                    50 => 'oklch(0.97 0.014 252)',
                    100 => 'oklch(0.932 0.032 252)',
                    200 => 'oklch(0.882 0.059 252)',
                    300 => 'oklch(0.809 0.09 252)',
                    400 => 'oklch(0.68 0.128 252)',
                    500 => 'oklch(0.555 0.13 252)',
                    600 => 'oklch(0.5 0.125 252)',
                    700 => 'oklch(0.45 0.115 252)',
                    800 => 'oklch(0.4 0.1 252)',
                    900 => 'oklch(0.35 0.08 252)',
                    950 => 'oklch(0.27 0.06 252)',
                ],
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString('<style>.fi-ta-text, .fi-wi-stats-overview-stat-value { font-variant-numeric: tabular-nums; }</style>'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
