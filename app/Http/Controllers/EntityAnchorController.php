<?php

namespace App\Http\Controllers;

use App\Models\EntityAnchor;
use Illuminate\Http\Request;

class EntityAnchorController extends Controller
{
    /**
     * Display a listing of the resource for the specified polymorphic entity.
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
        ]);

        $anchors = EntityAnchor::where('anchorable_type', $request->type)
            ->where('anchorable_id', $request->id)
            ->get();

        return response()->json($anchors);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
            'x' => 'required|integer|min:0|max:31',
            'y' => 'required|integer|min:0|max:31',
        ]);

        // Prevent duplicates at the same coordinate
        $exists = EntityAnchor::where('anchorable_type', $request->type)
            ->where('anchorable_id', $request->id)
            ->where('x', $request->x)
            ->where('y', $request->y)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Un\'ancora esiste già in questa posizione.'], 422);
        }

        $anchor = EntityAnchor::create([
            'anchorable_type' => $request->type,
            'anchorable_id' => $request->id,
            'x' => $request->x,
            'y' => $request->y,
        ]);

        return response()->json(['success' => true, 'anchor' => $anchor]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EntityAnchor $entityAnchor)
    {
        $entityAnchor->delete();

        return response()->json(['success' => true]);
    }
}
