<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Region;
use App\Observers\RegionObserver;
use App\Models\ElementHasPosition;
use App\Observers\ElementHasPositionObserver;
use App\Models\Player;
use App\Observers\PlayerObserver;
use App\Models\BrainSchedule;
use App\Observers\BrainScheduleObserver;
use App\Models\DrawRequest;
use App\Observers\DrawRequestObserver;
use App\Models\ElementHasPositionNeuron;
use App\Observers\ElementHasPositionNeuronObserver;
use App\Models\RuleChimicalElement;
use App\Observers\RuleChimicalElementObserver;

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
        Player::observe(PlayerObserver::class);
        BrainSchedule::observe(BrainScheduleObserver::class);
        DrawRequest::observe(DrawRequestObserver::class);
        ElementHasPositionNeuron::observe(ElementHasPositionNeuronObserver::class);
        RuleChimicalElement::observe(RuleChimicalElementObserver::class);

    }
}
