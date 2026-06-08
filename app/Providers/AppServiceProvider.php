<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Filament\Support\Facades\FilamentAsset::register([
            \Filament\Support\Assets\Css::make('custom-scrollbar', public_path('css/custom-scrollbar.css')),
        ]);

        \App\Models\DesignTask::observe(\App\Observers\DesignTaskObserver::class);
        \App\Models\ProductionWorkOrder::observe(\App\Observers\ProductionWorkOrderObserver::class);
    }
}
