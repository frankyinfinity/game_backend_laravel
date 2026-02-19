<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PhaseColumn;
use App\Models\Phase;
use App\Models\Age;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class PhaseColumnController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Age $age, Phase $phase)
    {
        $request->validate([
            'uid' => 'required|string',
        ]);

        $phase->phaseColumns()->create($request->all());

        return response()->json(['success' => true, 'message' => 'Fascia creata con successo.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Age $age, Phase $phase, PhaseColumn $phaseColumn)
    {
        $phaseColumn->delete();

        return response()->json(['success' => true, 'message' => 'Fascia eliminata con successo.']);
    }
}
