<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Star;
use App\Models\StarZone;

class StarController extends Controller
{

    public function index()
    {
        return view("star.index");
    }

    public function listDataTable(Request $request)
    {
        $query = Star::query()->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("star.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Star::query()->create([
            "name" => $request->name,
            "rgb" => $request->rgb,
            "description" => $request->description,
        ]);

        return redirect(route('stars.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $star = Star::query()->findOrFail($id);
        return view("star.show", compact("star"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $star = Star::query()->findOrFail($id);
        $zones = $star->zones()->orderBy('from')->get();
        return view("star.edit", compact("star", "zones"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $star = Star::query()->findOrFail($id);

        $star->update([
            "name" => $request->name,
            "rgb" => $request->rgb,
            "description" => $request->description,
        ]);

        return redirect(route('stars.show', [$id]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function delete(Request $request)
    {
        foreach ($request->ids as $id) {
            $star = Star::find($id);
            if ($star == null) continue;
            $star->delete();
        }
        return response()->json(['success' => true]);
    }

    /**
     * Cambia lo stato della stella (CREATED <-> ZONES).
     */
    public function updateState(Request $request, string $id)
    {
        $star = Star::query()->findOrFail($id);

        $request->validate([
            'state' => 'required|integer|in:' . Star::STATE_CREATED . ',' . Star::STATE_ZONES,
        ]);

        $star->update(['state' => $request->state]);

        if ((int) $request->state === Star::STATE_ZONES) {
            return redirect(route('stars.edit', [$star->id]));
        }

        return redirect()->back();
    }

    /**
     * Aggiunge una fascia (StarZone) alla stella.
     */
    public function storeZone(Request $request, string $id)
    {
        $star = Star::query()->findOrFail($id);

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'from'      => 'required|integer|min:0',
            'to'        => 'required|integer|gt:from',
            'rgb'       => 'required|string|max:64',
            'habitable' => 'required|boolean',
        ]);

        $zone = $star->zones()->create($validated);

        $zones = $star->zones()->orderBy('from')->get();

        return response()->json(['success' => true, 'zone' => $zone, 'zones' => $zones]);
    }

    /**
     * Aggiorna una fascia della stella.
     */
    public function updateZone(Request $request, string $id, string $zoneId)
    {
        $star = Star::query()->findOrFail($id);
        $zone = StarZone::query()->where('star_id', $star->id)->findOrFail($zoneId);

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'from'      => 'required|integer|min:0',
            'to'        => 'required|integer|gt:from',
            'rgb'       => 'required|string|max:64',
            'habitable' => 'required|boolean',
        ]);

        $zone->update($validated);

        $zones = $star->zones()->orderBy('from')->get();

        return response()->json(['success' => true, 'zone' => $zone, 'zones' => $zones]);
    }

    /**
     * Elimina una fascia della stella.
     */
    public function destroyZone(string $id, string $zoneId)
    {
        $star = Star::query()->findOrFail($id);
        $zone = StarZone::query()->where('star_id', $star->id)->findOrFail($zoneId);
        $zone->delete();

        $zones = $star->zones()->orderBy('from')->get();

        return response()->json(['success' => true, 'zones' => $zones]);
    }

}
