<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gene;
use Illuminate\Http\Request;

class GeneController extends Controller
{

    public function getRegistration(): \Illuminate\Http\JsonResponse
    {
        $genes = Gene::query()->where('show_on_registration', true)->get();
        return response()->json(['success' => true, 'genes' => $genes]);
    }

}
