<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Region;
use App\Observers\RegionObserver;
use App\Models\ElementHasPosition;
use App\Observers\ElementHasPositionObserver;
use App\Models\Player;
use App\Observers\PlayerObserver;
use Illuminate\Support\Facades\Broadcast;
use App\Broadcasting\SocketIoBroadcaster;
use GuzzleHttp\Client;

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

        // Register Socket.io broadcaster
        Broadcast::extend('socketio', function ($app) {
            return new SocketIoBroadcaster(
                new Client(),
                config('broadcasting.connections.socketio.url')
            );
        });
    }
}
