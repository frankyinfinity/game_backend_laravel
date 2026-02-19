<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TargetLink;
use App\Models\Target;
use App\Models\PhaseColumn;
use App\Models\Phase;
use App\Models\Age;
use Illuminate\Http\Request;

class TargetLinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function phaseIndex(Age $age, Phase $phase)
    {
        $links = TargetLink::whereHas('fromTarget.phaseColumn.phase', function ($query) use ($phase) {
            $query->where('id', $phase->id);
        })->orWhereHas('toTarget.phaseColumn.phase', function ($query) use ($phase) {
            $query->where('id', $phase->id);
        })->with('fromTarget', 'toTarget')->get();

        return response()->json(['success' => true, 'links' => $links]);
    }

    public function index(Age $age, Phase $phase, PhaseColumn $phaseColumn, Target $target)
    {
        $links = TargetLink::where('from_target_id', $target->id)
            ->orWhere('to_target_id', $target->id)
            ->with('fromTarget', 'toTarget')
            ->get();

        return response()->json(['success' => true, 'links' => $links]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'from_target_id' => 'required|exists:targets,id',
            'to_target_id' => 'required|exists:targets,id|different:from_target_id',
        ]);

        $link = TargetLink::create($request->all());

        return response()->json(['success' => true, 'message' => 'Collegamento creato con successo.', 'link' => $link]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Age $age, Phase $phase, PhaseColumn $phaseColumn, Target $target, TargetLink $targetLink)
    {
        return response()->json(['success' => true, 'link' => $targetLink->load('fromTarget', 'toTarget')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Age $age, Phase $phase, PhaseColumn $phaseColumn, Target $target, TargetLink $targetLink)
    {
        $targetLink->delete();

        return response()->json(['success' => true, 'message' => 'Collegamento eliminato con successo.']);
    }
}
