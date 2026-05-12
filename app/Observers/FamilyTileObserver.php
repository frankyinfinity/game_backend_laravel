<?php

namespace App\Observers;

use App\Models\FamilyTile;
use App\Models\Tile;
use Illuminate\Support\Facades\DB;

class FamilyTileObserver
{
    /**
     * Handle the FamilyTile "created" event.
     */
    public function created(FamilyTile $familyTile): void
    {
        //
    }

    /**
     * Handle the FamilyTile "updated" event.
     */
    public function updated(FamilyTile $familyTile): void
    {
        if ($familyTile->wasChanged('type')) {
            // Update all tiles with this family to have the new type without triggering events
            DB::table('tiles')->where('family_tile_id', $familyTile->id)->update(['type' => $familyTile->type]);
        }
    }

    /**
     * Handle the FamilyTile "deleted" event.
     */
    public function deleted(FamilyTile $familyTile): void
    {
        //
    }

    /**
     * Handle the FamilyTile "restored" event.
     */
    public function restored(FamilyTile $familyTile): void
    {
        //
    }

    /**
     * Handle the FamilyTile "force deleted" event.
     */
    public function forceDeleted(FamilyTile $familyTile): void
    {
        //
    }
}
