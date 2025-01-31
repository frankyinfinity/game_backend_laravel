<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gene;
use Illuminate\Http\Request;

class GeneController extends Controller
{
    
    public function getStarted(){
        $genes = Gene::query()->where('started', true)->get();
        return response()->json(['success' => true, 'genes' => $genes]);
    }

}
