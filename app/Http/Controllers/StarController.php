<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Star;
use App\Models\StarZone;
use App\Models\StarZoneHasPlanet;
use App\Models\Planet;

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
        $zones = $star->zones()->with('zonePlanets.planet')->orderBy('from')->get();
        return view("star.show", compact("star", "zones"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $star = Star::query()->findOrFail($id);
        $zones = $star->zones()->with('zonePlanets.planet')->orderBy('from')->get();
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
            'state' => 'required|integer|in:' . Star::STATE_CREATED . ',' . Star::STATE_ZONES . ',' . Star::STATE_PLANETS,
        ]);

        $star->update(['state' => $request->state]);

        if ((int) $request->state === Star::STATE_PLANETS) {
            return redirect()->to(route('stars.show', [$star->id]) . '#tab-pianeti');
        }

        if ((int) $request->state !== Star::STATE_CREATED) {
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
     * Posiziona un pianeta su un'orbita (raggio) dentro una fascia della stella.
     */
    public function storeZonePlanet(Request $request, string $id)
    {
        $star = Star::query()->findOrFail($id);
        $zoneIds = $star->zones()->pluck('id')->implode(',');

        $request->validate([
            'star_zone_id' => 'required|integer|in:' . $zoneIds,
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'radius'       => 'required|integer|min:0',
        ]);

        $planet = Planet::create([
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        $star->zones()->findOrFail($request->star_zone_id)->zonePlanets()->create([
            'planet_id' => $planet->id,
            'radius'    => $request->radius,
        ]);

        $zones = $star->zones()->with('zonePlanets.planet')->orderBy('from')->get();

        return response()->json(['success' => true, 'zones' => $zones]);
    }

    /**
     * Rimuove un pianeta dall'orbita.
     */
    public function destroyZonePlanet(string $id, string $zonePlanetId)
    {
        $star = Star::query()->findOrFail($id);

        $zonePlanet = StarZoneHasPlanet::query()
            ->whereHas('zone', function ($q) use ($star) {
                $q->where('star_id', $star->id);
            })
            ->findOrFail($zonePlanetId);
        $zonePlanet->delete();

        $zones = $star->zones()->with('zonePlanets.planet')->orderBy('from')->get();

        return response()->json(['success' => true, 'zones' => $zones]);
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
