<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EntityController extends Controller
{

    /**
     * Recupera la posizione attuale di un'entity tramite uid
     */
    public function position(Request $request)
    {
        $uid = $request->query('uid');

        if (!$uid) {
            return response()->json([
                'success' => false,
                'message' => 'UID non fornito'
            ], 400);
        }

        $entity = Entity::where('uid', $uid)->first();

        if (!$entity) {
            return response()->json([
                'success' => false,
                'message' => 'Entity non trovata'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'uid' => $entity->uid,
            'tile_i' => $entity->tile_i,
            'tile_j' => $entity->tile_j,
        ]);
    }

    /**
     * Recupera i valori attuali dei geni di un'entity tramite uid
     */
    public function genes(Request $request)
    {
        $uid = $request->query('uid');

        if (!$uid) {
            return response()->json([
                'success' => false,
                'message' => 'UID non fornito'
            ], 400);
        }

        $entity = Entity::where('uid', $uid)->first();

        if (!$entity) {
            return response()->json([
                'success' => false,
                'message' => 'Entity non trovata'
            ], 404);
        }

        $genesData = [];
        $entity->load(['genomes.gene', 'genomes.entityInformations']);

        foreach ($entity->genomes as $genome) {
            $gene = $genome->gene;
            // Prende l'ultimo valore registrato o l'unico disponibile
            $currentValue = $genome->entityInformations->last()->value ?? 0;

            $genesData[] = [
                'id' => $gene->id,
                'key' => $gene->key,
                'name' => $gene->name,
                'value' => $currentValue,
                'min' => $genome->min,
                'max' => $genome->max,
                'modifier' => $genome->modifier,
            ];
            \Log::info('[EntityController] gene: ' . $gene->key . ' modifier: ' . $genome->modifier);
        }

        return response()->json([
            'success' => true,
            'uid' => $entity->uid,
            'genes' => $genesData
        ]);
    }

    /**
     * Recupera i valori attuali degli elementi chimici di un'entity tramite uid
     */
    public function chimicalElements(Request $request)
    {
        $uid = $request->query('uid');

        if (!$uid) {
            return response()->json([
                'success' => false,
                'message' => 'UID non fornito'
            ], 400);
        }

        $entity = Entity::where('uid', $uid)->first();

        if (!$entity) {
            return response()->json([
                'success' => false,
                'message' => 'Entity non trovata'
            ], 404);
        }

        $chimicalData = [];
        $entity->load(['chimicalElements.playerRuleChimicalElement', 'chimicalElements.playerRuleChimicalElement.details']);

        foreach ($entity->chimicalElements as $entityChimical) {
            $ruleChimical = $entityChimical->playerRuleChimicalElement;
            if (!$ruleChimical)
                continue;

            $chimicalData[] = [
                'id' => $entityChimical->id,
                'value' => (int) $entityChimical->value,
                'min' => (int) $ruleChimical->min,
                'max' => (int) $ruleChimical->max,
            ];
        }

        return response()->json([
            'success' => true,
            'uid' => $entity->uid,
            'chimical_elements' => $chimicalData
        ]);
    }

}
