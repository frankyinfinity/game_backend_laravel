<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Planet;

class PlanetController extends Controller
{
    
    public function get(){
        $planets = Planet::query()->orderBy('name')->get();
        return response()->json(['success' => true, 'planets' => $planets]);
    }

}
