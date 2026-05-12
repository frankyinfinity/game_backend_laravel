<?php

namespace App\Observers;

use App\Models\Tile;
use App\Models\FamilyTile;

class TileObserver
{
    /**
     * Handle the Tile "created" event.
     */
    public function created(Tile $tile): void
    {
        $this->setTypeFromFamily($tile);
    }

    /**
     * Handle the Tile "updated" event.
     */
    public function updated(Tile $tile): void
    {
        if ($tile->wasChanged('family_tile_id')) {
            $this->setTypeFromFamily($tile);
        }
    }

    /**
     * Handle the Tile "deleted" event.
     */
    public function deleted(Tile $tile): void
    {
        //
    }

    /**
     * Handle the Tile "restored" event.
     */
    public function restored(Tile $tile): void
    {
        //
    }

    /**
     * Handle the Tile "force deleted" event.
     */
    public function forceDeleted(Tile $tile): void
    {
        //
    }

    private function setTypeFromFamily(Tile $tile): void
    {
        if ($tile->family_tile_id) {
            $family = FamilyTile::find($tile->family_tile_id);
            if ($family) {
                $tile->updateQuietly(['type' => $family->type]);
            }
        }
    }
}
