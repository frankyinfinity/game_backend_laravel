<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EntityBody;
use App\Models\EntityBodyZone;
use App\Models\EntityBodyZoneDetail;
use Illuminate\Http\Request;

class EntityBodyZoneController extends Controller
{
    /**
     * Return all zones for a given entity body as JSON.
     */
    public function index(Request $request, EntityBody $entityBody)
    {
        $zones = $entityBody->zones()->with(['details', 'pixels'])->orderBy('name')->get();
        return response()->json($zones);
    }

    /**
     * Store a new zone.
     */
    public function store(Request $request, EntityBody $entityBody)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
        ]);

        $zone = $entityBody->zones()->create([
            'name' => $request->name,
            'color' => $request->color,
        ]);

        return response()->json($zone->load(['details', 'pixels']));
    }

    /**
     * Update zone name.
     */
    public function update(Request $request, EntityBody $entityBody, EntityBodyZone $zone)
    {
        if ($zone->entity_body_id !== $entityBody->id) {
            return response()->json(['success' => false, 'message' => 'Zona non trovata.'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $zone->update(['name' => $request->name]);

        return response()->json($zone->load(['details', 'pixels']));
    }

    /**
     * Delete a zone (cascade deletes details).
     */
    public function destroy(Request $request, EntityBody $entityBody, EntityBodyZone $zone)
    {
        if ($zone->entity_body_id !== $entityBody->id) {
            return response()->json(['success' => false, 'message' => 'Zona non trovata.'], 404);
        }

        $zone->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Add a coordinate detail to a zone.
     */
    public function addDetail(Request $request, EntityBody $entityBody, EntityBodyZone $zone)
    {
        if ($zone->entity_body_id !== $entityBody->id) {
            return response()->json(['success' => false, 'message' => 'Zona non trovata.'], 404);
        }

        $request->validate([
            'x' => 'required|integer|min:0',
            'y' => 'required|integer|min:0',
        ]);

        $detail = EntityBodyZoneDetail::create([
            'entity_body_zone_id' => $zone->id,
            'x' => $request->x,
            'y' => $request->y,
        ]);

        return response()->json($detail);
    }

    /**
     * Remove a coordinate detail from a zone.
     */
    public function removeDetail(Request $request, EntityBody $entityBody, EntityBodyZone $zone, EntityBodyZoneDetail $detail)
    {
        if ($detail->entity_body_zone_id !== $zone->id || $zone->entity_body_id !== $entityBody->id) {
            return response()->json(['success' => false, 'message' => 'Dettaglio non trovato.'], 404);
        }

        $detail->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Bulk replace details for a zone with a fresh list of coordinates.
     */
    public function replaceDetails(Request $request, EntityBody $entityBody, EntityBodyZone $zone)
    {
        if ($zone->entity_body_id !== $entityBody->id) {
            return response()->json(['success' => false, 'message' => 'Zona non trovata.'], 404);
        }

        $request->validate([
            'details' => 'required|array',
            'details.*.x' => 'required|integer|min:0',
            'details.*.y' => 'required|integer|min:0',
        ]);

        $zone->details()->delete();

        foreach ($request->details as $coords) {
            EntityBodyZoneDetail::create([
                'entity_body_zone_id' => $zone->id,
                'x' => $coords['x'],
                'y' => $coords['y'],
            ]);
        }

        $zone->load(['details', 'pixels']);
        return response()->json($zone);
    }

    /**
     * Bulk save pixels for a zone.
     */
    public function savePixels(Request $request, EntityBody $entityBody, EntityBodyZone $zone)
    {
        if ($zone->entity_body_id !== $entityBody->id) {
            return response()->json(['success' => false, 'message' => 'Zona non trovata.'], 404);
        }

        $request->validate([
            'pixels' => 'required|array',
            'pixels.*.x' => 'required|integer|min:0',
            'pixels.*.y' => 'required|integer|min:0',
        ]);

        $zone->pixels()->delete();

        // Trova tutti i pixel già occupati da altre zone di questo entityBody
        $otherZoneIds = EntityBodyZone::where('entity_body_id', $entityBody->id)
            ->where('id', '!=', $zone->id)
            ->pluck('id');

        $occupiedPixels = \App\Models\EntityBodyZonePixel::whereIn('entity_body_zone_id', $otherZoneIds)
            ->select('x', 'y')
            ->get()
            ->mapWithKeys(function ($px) {
                return ["{$px->x},{$px->y}" => true];
            })
            ->toArray();

        $insertData = [];
        $now = now();
        foreach ($request->pixels as $coords) {
            $key = "{$coords['x']},{$coords['y']}";
            if (isset($occupiedPixels[$key])) {
                continue; // Salta i pixel già assegnati ad un'altra zona
            }
            $insertData[] = [
                'entity_body_zone_id' => $zone->id,
                'x' => $coords['x'],
                'y' => $coords['y'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($insertData)) {
            foreach (array_chunk($insertData, 1000) as $chunk) {
                \App\Models\EntityBodyZonePixel::insert($chunk);
            }
        }

        return response()->json(['success' => true]);
    }
}
