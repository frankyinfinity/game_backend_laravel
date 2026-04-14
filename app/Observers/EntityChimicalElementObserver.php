<?php

namespace App\Observers;

use App\Models\EntityChimicalElement;
use App\Models\PlayerModifier;
use App\Models\PlayerRuleChimicalElementDetailEffect;
use Illuminate\Support\Facades\Log;

class EntityChimicalElementObserver
{
    public function updated(EntityChimicalElement $entityChimicalElement): void
    {
        if ($entityChimicalElement->isDirty('value')) {
            $oldValue = $entityChimicalElement->getOriginal('value');
            $newValue = $entityChimicalElement->value;

            Log::info('EntityChimicalElement updated: entity_id=' . $entityChimicalElement->entity_id . ', old_value=' . $oldValue . ', new_value=' . $newValue);

            $playerRuleChimicalElement = $entityChimicalElement->playerRuleChimicalElement;
            if (!$playerRuleChimicalElement) {
                return;
            }

            $playerId = $playerRuleChimicalElement->player_id;
            $entity = $entityChimicalElement->entity;
            if (!$entity) {
                return;
            }

            $details = $playerRuleChimicalElement->details()->with('effects.gene')->orderBy('min')->get();

            $activeFixedEffectIds = [];
            $allFixedEffectIds = [];

            foreach ($details as $detail) {
                foreach ($detail->effects as $effect) {
                    if ($effect->type === PlayerRuleChimicalElementDetailEffect::TYPE_FIXED) {
                        $allFixedEffectIds[] = $effect->id;
                    }
                }

                if ($newValue >= $detail->min && $newValue <= $detail->max) {
                    foreach ($detail->effects as $effect) {
                        $genomeId = $entity->genomes()->where('gene_id', $effect->gene_id)->first()?->id;

                        $activeFixedEffectIds[] = $effect->id;

                        $exists = PlayerModifier::query()
                            ->where('player_id', $playerId)
                            ->where('effect_id', $effect->id)
                            ->where('genome_id', $genomeId)
                            ->exists();

                        if (!$exists) {

                            $fields = [
                                'player_id' => $playerId,
                                'effect_id' => $effect->id,
                                'genome_id' => $genomeId,
                            ];
                            if ($effect->type === PlayerRuleChimicalElementDetailEffect::TYPE_TIMED) {
                                Log::info('WIP: TYPE_TIMED effect not yet implemented');
                            }

                            PlayerModifier::create($fields);

                        }

                    }
                }
            }

            if (!empty($allFixedEffectIds)) {
                $toDelete = array_diff($allFixedEffectIds, $activeFixedEffectIds);
                if (!empty($toDelete)) {
                    PlayerModifier::where('player_id', $playerId)
                        ->whereIn('effect_id', $toDelete)
                        ->delete();
                }
            }
        }
    }
}
