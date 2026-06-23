<?php

namespace App\Http\Controllers;

use App\Models\ElementBody;
use App\Models\ElementBodyZone;
use App\Models\ElementBodyZoneDetail;
use App\Models\ElementBodyZonePixel;
use Illuminate\Http\Request;

class ElementBodyZoneController extends Controller
{
    public function index(Request $request, ElementBody $elementBody)
    {
        return response()->json($elementBody->zones()->with(['details', 'pixels'])->orderBy('name')->get());
    }

    public function store(Request $request, ElementBody $elementBody)
    {
        if ($elementBody->state >= 2) return response()->json(['success' => false, 'message' => 'Modifiche bloccate.'], 403);
        $request->validate(['name' => 'required|string|max:255', 'color' => 'nullable|string|max:7']);
        $zone = $elementBody->zones()->create(['name' => $request->name, 'color' => $request->color]);
        return response()->json($zone->load(['details', 'pixels']));
    }

    public function update(Request $request, ElementBody $elementBody, ElementBodyZone $zone)
    {
        if ($zone->element_body_id !== $elementBody->id) return response()->json(['success' => false], 404);
        if ($elementBody->state >= 2) return response()->json(['success' => false, 'message' => 'Modifiche bloccate.'], 403);
        $request->validate(['name' => 'required|string|max:255']);
        $zone->update(['name' => $request->name]);
        return response()->json($zone->load(['details', 'pixels']));
    }

    public function destroy(Request $request, ElementBody $elementBody, ElementBodyZone $zone)
    {
        if ($zone->element_body_id !== $elementBody->id) return response()->json(['success' => false], 404);
        if ($elementBody->state >= 2) return response()->json(['success' => false, 'message' => 'Modifiche bloccate.'], 403);
        $zone->delete();
        return response()->json(['success' => true]);
    }

    public function addDetail(Request $request, ElementBody $elementBody, ElementBodyZone $zone)
    {
        if ($zone->element_body_id !== $elementBody->id) return response()->json(['success' => false], 404);
        if ($elementBody->state >= 2) return response()->json(['success' => false, 'message' => 'Modifiche bloccate.'], 403);
        $request->validate(['x' => 'required|integer|min:0', 'y' => 'required|integer|min:0']);
        $detail = ElementBodyZoneDetail::create(['element_body_zone_id' => $zone->id, 'x' => $request->x, 'y' => $request->y]);
        return response()->json($detail);
    }

    public function removeDetail(Request $request, ElementBody $elementBody, ElementBodyZone $zone, ElementBodyZoneDetail $detail)
    {
        if ($detail->element_body_zone_id !== $zone->id || $zone->element_body_id !== $elementBody->id) return response()->json(['success' => false], 404);
        if ($elementBody->state >= 2) return response()->json(['success' => false, 'message' => 'Modifiche bloccate.'], 403);
        $detail->delete();
        return response()->json(['success' => true]);
    }

    public function replaceDetails(Request $request, ElementBody $elementBody, ElementBodyZone $zone)
    {
        if ($zone->element_body_id !== $elementBody->id) return response()->json(['success' => false], 404);
        if ($elementBody->state >= 2) return response()->json(['success' => false, 'message' => 'Modifiche bloccate.'], 403);
        $request->validate(['details' => 'required|array', 'details.*.x' => 'required|integer|min:0', 'details.*.y' => 'required|integer|min:0']);
        $zone->details()->delete();
        foreach ($request->details as $coords) {
            ElementBodyZoneDetail::create(['element_body_zone_id' => $zone->id, 'x' => $coords['x'], 'y' => $coords['y']]);
        }
        return response()->json($zone->load(['details', 'pixels']));
    }

    public function savePixels(Request $request, ElementBody $elementBody, ElementBodyZone $zone)
    {
        if ($zone->element_body_id !== $elementBody->id) return response()->json(['success' => false], 404);
        if ($elementBody->state >= 2) return response()->json(['success' => false, 'message' => 'Modifiche bloccate.'], 403);
        $request->validate(['pixels' => 'required|array', 'pixels.*.x' => 'required|integer|min:0', 'pixels.*.y' => 'required|integer|min:0']);
        $zone->pixels()->delete();
        $otherZoneIds = ElementBodyZone::where('element_body_id', $elementBody->id)->where('id', '!=', $zone->id)->pluck('id');
        $occupied = ElementBodyZonePixel::whereIn('element_body_zone_id', $otherZoneIds)->select('x', 'y')->get()->mapWithKeys(fn($px) => ["{$px->x},{$px->y}" => true])->toArray();
        $now = now();
        $insert = [];
        foreach ($request->pixels as $c) {
            if (isset($occupied["{$c['x']},{$c['y']}"])) continue;
            $insert[] = ['element_body_zone_id' => $zone->id, 'x' => $c['x'], 'y' => $c['y'], 'created_at' => $now, 'updated_at' => $now];
        }
        if (!empty($insert)) foreach (array_chunk($insert, 1000) as $chunk) ElementBodyZonePixel::insert($chunk);
        return response()->json(['success' => true]);
    }
}
