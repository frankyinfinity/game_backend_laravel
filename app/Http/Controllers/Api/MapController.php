<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Region;

class MapController extends Controller
{
    
    public function get($region_id){

        $region = Region::query()->where('id', $region_id)->first();

        $filename = $region->filename;
        $jsonContent = Storage::disk('regions')->get($region->id.'/'.$filename);
        $json = json_decode($jsonContent, true);
        return response()->json(['success' => true, 'map' => $json]);

    }

}
