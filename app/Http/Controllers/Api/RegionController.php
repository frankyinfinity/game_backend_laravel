<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Region;

class RegionController extends Controller
{

    public function get($planet_id){
        $regions = Region::query()->where('planet_id', $planet_id)->orderBy('name')->whereNotNull('filename')->get();
        return response()->json(['success' => true, 'regions' => $regions]);
    }
    
}
