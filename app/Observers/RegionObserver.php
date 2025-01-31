<?php

namespace App\Observers;
use App\Models\Region;
use Illuminate\Support\Facades\Storage;

class RegionObserver
{
    
    public function deleted(Region $region): void {
        if($region->filename != null) {
            Storage::disk('regions')->delete($region->id.'/'.$region->filename);
        }
    }

}
