<?php

namespace App\Observers;

use App\Models\Entity;
use App\Models\EvolutionStep;
use App\Models\PlayerValue;
use Illuminate\Support\Facades\Log;

class EntityObserver
{
    /**
     * Handle the Entity "created" event.
     */
    public function created(Entity $entity): void
    {
        // Se l'entity ha un evolution_step_id, salvalo in PlayerValue
        if ($entity->evolution_step_id) {
            $playerId = $entity->specie->player_id;
            PlayerValue::setValue(
                $playerId,
                PlayerValue::KEY_LAST_EVOLUTION_STEP_ID,
                (string) $entity->evolution_step_id,
                PlayerValue::TYPE_STRING
            );

            Log::info('EntityObserver: LAST_EVOLUTION_STEP_ID salvato', [
                'entity_id' => $entity->id,
                'entity_uid' => $entity->uid,
                'evolution_step_id' => $entity->evolution_step_id,
                'player_id' => $playerId,
            ]);
        }
    }

    /**
     * Handle the Entity "updated" event.
     */
    public function updated(Entity $entity): void
    {
        // Se l'entity è morta e ha un evolution_step_id, decrementa la popolazione dello step
        if ($entity->wasChanged('state') && $entity->state === Entity::STATE_DEATH) {
            if ($entity->evolution_step_id) {
                $evolutionStep = EvolutionStep::query()->find($entity->evolution_step_id);

                if ($evolutionStep) {
                    // Decrementa la popolazione (minimo 0)
                    $evolutionStep->decrement('population');
                    $evolutionStep->refresh();

                    // Se la popolazione scende sotto 0, correggi a 0
                    if ($evolutionStep->population < 0) {
                        $evolutionStep->update(['population' => 0]);
                    }

                    Log::info('EntityObserver: population decrementata per morte entity', [
                        'entity_id' => $entity->id,
                        'entity_uid' => $entity->uid,
                        'evolution_step_id' => $evolutionStep->id,
                        'new_population' => $evolutionStep->population,
                    ]);
                }
            }
        }
    }
}
