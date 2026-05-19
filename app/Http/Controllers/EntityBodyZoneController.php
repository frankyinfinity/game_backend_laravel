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
        $zones = $entityBody->zones()->with('details')->orderBy('name')->get();
        return response()->json($zones);
    }

    /**
     * Store a new zone.
     */
    public function store(Request $request, EntityBody $entityBody)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $zone = $entityBody->zones()->create([
            'name' => $request->name,
        ]);

        return response()->json($zone->load('details'));
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

        return response()->json($zone->load('details'));
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

        $zone->load('details');
        return response()->json($zone);
    }
}
