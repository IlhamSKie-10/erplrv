<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/') // Panel accessible at root URL
            ->brandName('ERP Produksi')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->sidebarFullyCollapsibleOnDesktop()
            ->sidebarWidth('15rem')
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('CS (Input Pesanan)')
                    ->url(fn (): string => \App\Filament\Resources\OrderResource::getUrl('create'))
                    ->icon('heroicon-o-plus-circle')
                    ->group('Customer Service')
                    ->sort(1),
            ])
            ->maxContentWidth('full')
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => \Illuminate\Support\Facades\Blade::render('
                    <style>
                        /* Modern Responsive Scrollbar for Sidebar */
                        .fi-sidebar-nav {
                            overflow-y: auto !important;
                            overscroll-behavior: contain;
                            scrollbar-width: thin; /* Firefox */
                            scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
                        }
                        .fi-sidebar-nav::-webkit-scrollbar {
                            width: 5px;
                            background-color: transparent;
                        }
                        .fi-sidebar-nav::-webkit-scrollbar-track {
                            background: transparent;
                        }
                        .fi-sidebar-nav::-webkit-scrollbar-thumb {
                            background-color: rgba(156, 163, 175, 0.5);
                            border-radius: 10px;
                        }
                        .fi-sidebar-nav:hover::-webkit-scrollbar-thumb {
                            background-color: rgba(107, 114, 128, 0.8);
                        }
                        .dark .fi-sidebar-nav::-webkit-scrollbar-thumb {
                            background-color: rgba(75, 85, 99, 0.5);
                        }
                        .dark .fi-sidebar-nav:hover::-webkit-scrollbar-thumb {
                            background-color: rgba(107, 114, 128, 0.8);
                        }
                    </style>
                ')
            )
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
