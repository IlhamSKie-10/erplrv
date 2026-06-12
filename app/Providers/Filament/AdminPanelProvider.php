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
                        
                        /* Table Drag to Scroll Cursor */
                        .fi-ta-content {
                            cursor: grab;
                        }
                        .fi-ta-content:active {
                            cursor: grabbing;
                        }
                        /* Hide default bottom scrollbar for table container */
                        .fi-ta-content::-webkit-scrollbar {
                            display: none;
                        }
                    </style>
                ')
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_END,
                fn (): string => \Illuminate\Support\Facades\Blade::render('
                    <script>
                        document.addEventListener("DOMContentLoaded", () => {
                            let isDown = false;
                            let startX;
                            let scrollLeft;
                            let slider;

                            document.addEventListener("mousedown", (e) => {
                                slider = e.target.closest(".fi-ta-content");
                                if (!slider) return;
                                
                                // Ignore interactive elements
                                const isInteractive = e.target.closest("button, a, input, select, textarea, [role=\'button\'], label");
                                if (isInteractive) {
                                    slider = null;
                                    return;
                                }

                                isDown = true;
                                slider.style.cursor = "grabbing";
                                startX = e.pageX - slider.offsetLeft;
                                scrollLeft = slider.scrollLeft;
                            });

                            document.addEventListener("mouseup", () => {
                                isDown = false;
                                if(slider) slider.style.cursor = "";
                            });

                            document.addEventListener("mousemove", (e) => {
                                if (!isDown || !slider) return;
                                
                                e.preventDefault(); // Mencegah highlight teks saat drag
                                const x = e.pageX - slider.offsetLeft;
                                const walk = (x - startX) * 1.5; // Kecepatan scroll
                                slider.scrollLeft = scrollLeft - walk;
                            });

                            // --- Top Scrollbar Logic ---
                            function initTopScrollbars() {
                                document.querySelectorAll(".fi-ta-content").forEach(container => {
                                    // Pastikan kita belum menambahkan scrollbar ke container ini
                                    if (container.parentElement.querySelector(".custom-top-scrollbar")) return;

                                    // Buat wrapper scrollbar di atas
                                    const topScroll = document.createElement("div");
                                    topScroll.className = "custom-top-scrollbar";
                                    topScroll.style.overflowX = "auto";
                                    topScroll.style.overflowY = "hidden";
                                    // Supaya style menyatu dengan desain admin
                                    topScroll.style.marginBottom = "4px";
                                    topScroll.style.borderRadius = "8px";
                                    
                                    // Sembunyikan scrollbar bawaan di bawah dengan CSS
                                    container.style.scrollbarWidth = "none"; // Firefox
                                    container.style.msOverflowStyle = "none"; // IE
                                    // (Untuk webkit kita tambahkan style di tag CSS di atas)

                                    const dummyContent = document.createElement("div");
                                    dummyContent.style.height = "1px";
                                    topScroll.appendChild(dummyContent);

                                    // Masukkan sebelum container tabel
                                    container.parentElement.insertBefore(topScroll, container);

                                    const syncWidths = () => {
                                        dummyContent.style.width = container.scrollWidth + "px";
                                        // Sembunyikan top scrollbar jika tidak ada scroll
                                        if (container.scrollWidth <= container.clientWidth) {
                                            topScroll.style.display = "none";
                                        } else {
                                            topScroll.style.display = "block";
                                        }
                                    };

                                    // Sinkronisasi ukuran
                                    syncWidths();
                                    const ro = new ResizeObserver(syncWidths);
                                    ro.observe(container);
                                    // Objek di dalam tabel mungkin berubah
                                    const table = container.querySelector("table");
                                    if(table) ro.observe(table);

                                    // Sinkronisasi scroll
                                    topScroll.addEventListener("scroll", () => {
                                        container.scrollLeft = topScroll.scrollLeft;
                                    });
                                    container.addEventListener("scroll", () => {
                                        topScroll.scrollLeft = container.scrollLeft;
                                    });
                                });
                            }

                            // Jalankan inisialisasi
                            initTopScrollbars();

                            // Gunakan MutationObserver untuk mendeteksi perubahan Livewire (tabel di-load ulang)
                            const observer = new MutationObserver((mutations) => {
                                for (let mutation of mutations) {
                                    if (mutation.addedNodes.length) {
                                        initTopScrollbars();
                                    }
                                }
                            });
                            observer.observe(document.body, { childList: true, subtree: true });
                        });
                    </script>
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
