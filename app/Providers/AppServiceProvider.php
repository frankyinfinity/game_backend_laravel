<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Region;
use App\Observers\RegionObserver;
use App\Models\ElementHasPosition;
use App\Observers\ElementHasPositionObserver;

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
        Region::observe(RegionObserver::class);
        ElementHasPosition::observe(ElementHasPositionObserver::class);
    }
}
