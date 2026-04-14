<?php

namespace App\Http\Controllers;

use App\Models\RuleChimicalElement;
use App\Models\RuleChimicalElementDetail;
use App\Models\RuleChimicalElementDetailEffect;
use App\Models\Gene;
use Illuminate\Http\Request;

class RuleChimicalElementDetailController extends Controller
{
    public function storeEffect(Request $request, RuleChimicalElementDetail $detail)
    {
        $request->validate([
            'type' => 'required|integer|in:' . RuleChimicalElementDetailEffect::TYPE_FIXED . ',' . RuleChimicalElementDetailEffect::TYPE_TIMED,
            'gene_id' => 'required|integer|exists:genes,id',
            'value' => 'required|integer',
            'duration' => 'nullable|integer',
        ]);

        $effect = $detail->effects()->create([
            'type' => $request->input('type'),
            'gene_id' => $request->input('gene_id'),
            'value' => $request->input('value'),
            'duration' => $request->input('duration'),
        ]);

        return response()->json(['success' => true, 'effect' => $effect]);
    }

    public function updateEffect(Request $request, RuleChimicalElementDetailEffect $effect)
    {
        $request->validate([
            'type' => 'required|integer|in:' . RuleChimicalElementDetailEffect::TYPE_FIXED . ',' . RuleChimicalElementDetailEffect::TYPE_TIMED,
            'gene_id' => 'required|integer|exists:genes,id',
            'value' => 'required|integer',
            'duration' => 'nullable|integer',
        ]);

        $effect->update([
            'type' => $request->input('type'),
            'gene_id' => $request->input('gene_id'),
            'value' => $request->input('value'),
            'duration' => $request->input('duration'),
        ]);

        return response()->json(['success' => true, 'effect' => $effect]);
    }

    public function destroyEffect(RuleChimicalElementDetailEffect $effect)
    {
        $effect->delete();
        return response()->json(['success' => true]);
    }

    public function listEffects(RuleChimicalElementDetail $detail)
    {
        $detail->load('effects.gene');
        return response()->json([
            'effects' => $detail->effects->map(fn($e) => [
                'id' => $e->id,
                'type' => $e->type,
                'type_name' => $e->type === RuleChimicalElementDetailEffect::TYPE_FIXED ? 'fixed' : 'timed',
                'gene_id' => $e->gene_id,
                'gene_name' => $e->gene->name,
                'value' => $e->value,
                'duration' => $e->duration,
            ]),
        ]);
    }

    public function store(Request $request, RuleChimicalElement $ruleChimicalElement)
    {
        $request->validate([
            'min' => 'required|integer',
            'max' => 'required|integer',
            'color' => 'required|string|size:7',
        ]);

        $newMin = $request->input('min');
        $newMax = $request->input('max');

        if ($newMin >= $newMax) {
            return back()->withErrors('Min deve essere minore di Max');
        }

        $overlap = $ruleChimicalElement->details()
            ->where(function ($query) use ($newMin, $newMax) {
                $query->where(function ($q) use ($newMin, $newMax) {
                    $q->where('min', '<', $newMax)->where('max', '>', $newMin);
                });
            })
            ->exists();

        if ($overlap) {
            return back()->withErrors('Il range si sovrappone con un dettaglio esistente');
        }

        $ruleChimicalElement->details()->create([
            'min' => $newMin,
            'max' => $newMax,
            'color' => $request->input('color'),
        ]);

        return redirect()->route('rule-chimical-elements.show', $ruleChimicalElement->id);
    }

    public function destroy(RuleChimicalElement $ruleChimicalElement, RuleChimicalElementDetail $detail)
    {
        $detail->delete();
        return redirect()->route('rule-chimical-elements.show', $ruleChimicalElement->id);
    }

    public function saveAll(Request $request, RuleChimicalElement $ruleChimicalElement)
    {
        $detailsJson = $request->input('details', '[]');
        $details = json_decode($detailsJson, true);
        
        if (empty($details)) {
            return response()->json(['error' => 'No details received', 'received' => $detailsJson], 400);
        }
        
        $saved = 0;
        foreach ($details as $d) {
            $detail = RuleChimicalElementDetail::find($d['id']);
            if ($detail) {
                $detail->update([
                    'min' => $d['min'],
                    'max' => $d['max'],
                ]);
                $saved++;
            }
        }
        
        return response()->json(['success' => true, 'saved' => $saved]);
    }

    public function update(Request $request, RuleChimicalElement $ruleChimicalElement, RuleChimicalElementDetail $detail)
    {
        $request->validate([
            'min' => 'required|integer',
            'max' => 'required|integer',
        ]);

        $newMin = $request->input('min');
        $newMax = $request->input('max');

        if ($newMin >= $newMax) {
            return response()->json(['error' => 'Min must be less than max'], 400);
        }

        $overlap = $ruleChimicalElement->details()
            ->where('id', '!=', $detail->id)
            ->where(function ($query) use ($newMin, $newMax) {
                $query->where('min', '<', $newMax)->where('max', '>', $newMin);
            })
            ->exists();

        if ($overlap) {
            return response()->json(['error' => 'Overlap with existing detail'], 400);
        }

        $detail->update([
            'min' => $newMin,
            'max' => $newMax,
        ]);

        if ($request->has('color')) {
            $detail->update(['color' => $request->input('color')]);
        }

        return response()->json(['success' => true, 'detail' => $detail->fresh()]);
    }

    public function reload(RuleChimicalElement $ruleChimicalElement)
    {
        $ruleChimicalElement->load('details');
        return response()->json([
            'details' => $ruleChimicalElement->details->map(fn($d) => [
                'id' => $d->id,
                'min' => $d->min,
                'max' => $d->max,
                'color' => $d->color,
            ]),
            'ruleMin' => $ruleChimicalElement->min,
            'ruleMax' => $ruleChimicalElement->max,
        ]);
    }
}