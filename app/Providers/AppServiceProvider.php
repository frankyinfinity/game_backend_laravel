<?php

namespace App\Providers;

use App\Models\BirthRegion;
use App\Models\BrainSchedule;
use App\Models\DrawRequest;
use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionChimicalElement;
use App\Models\ElementHasPositionNeuron;
use App\Models\ElementModifier;
use App\Models\Entity;
use App\Models\EntityChimicalElement;
use App\Models\Neuron;
use App\Models\Player;
use App\Models\PlayerModifier;
use App\Models\Region;
use App\Models\RuleChimicalElement;
use App\Observers\BrainScheduleObserver;
use App\Observers\DrawRequestObserver;
use App\Observers\ElementHasPositionChimicalElementObserver;
use App\Observers\ElementHasPositionNeuronObserver;
use App\Observers\ElementHasPositionObserver;
use App\Observers\ElementModifierObserver;
use App\Observers\EntityChimicalElementObserver;
use App\Observers\NeuronObserver;
use App\Observers\PlayerModifierObserver;
use App\Observers\RegionObserver;
use App\Observers\RuleChimicalElementObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Morph map per Container::parent_type (usa valori corti invece di FQCN)
        Relation::morphMap([
            'Entity'             => Entity::class,
            'Player'             => Player::class,
            'Map'                => BirthRegion::class,
            'Objective'          => Player::class,
            'ElementHasPosition' => ElementHasPosition::class,
            'CacheSync'          => Player::class,
            'ChimicalElement'    => BirthRegion::class,
        ]);

        Region::observe(RegionObserver::class);
        ElementHasPosition::observe(ElementHasPositionObserver::class);
        BrainSchedule::observe(BrainScheduleObserver::class);
        DrawRequest::observe(DrawRequestObserver::class);
        ElementHasPositionNeuron::observe(ElementHasPositionNeuronObserver::class);
        RuleChimicalElement::observe(RuleChimicalElementObserver::class);
        EntityChimicalElement::observe(EntityChimicalElementObserver::class);
        PlayerModifier::observe(PlayerModifierObserver::class);
        ElementHasPositionChimicalElement::observe(ElementHasPositionChimicalElementObserver::class);
        ElementModifier::observe(ElementModifierObserver::class);
        Neuron::observe(NeuronObserver::class);
    }
}
