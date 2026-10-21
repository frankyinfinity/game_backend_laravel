<?php

namespace App\Observers;

use App\Models\Entity;
use App\Models\EvolutionStep;
use App\Models\PlayerValue;
use App\Models\TimelineEvolution;
use App\Models\TimelineEvolutionDetail;
use App\Models\TimelineEvolutionDetailEntity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

            // NON chiamare handleTimelineEvolution qui perché l'immagine non è ancora stata creata
            // Viene chiamato quando image viene impostato nell'evento updated
        }
    }

    /**
     * Handle the Entity "updated" event.
     */
    public function updated(Entity $entity): void
    {
        // Se l'immagine è stata appena impostata e l'entity ha un evolution_step_id, popola TimelineEvolution
        if ($entity->wasChanged('image') && $entity->image && $entity->evolution_step_id) {
            Log::info('EntityObserver: Immagine impostata, chiamo handleTimelineEvolution', [
                'entity_id' => $entity->id,
                'entity_uid' => $entity->uid,
                'image' => $entity->image,
                'evolution_step_id' => $entity->evolution_step_id,
            ]);
            $this->handleTimelineEvolution($entity);
        }

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

            // Decrementa anche popolution su TimelineEvolutionDetail se esiste
            $this->decrementTimelineEvolutionDetail($entity);
        }

        // Se evolution_step_id è stato appena impostato e l'immagine esiste già, popola TimelineEvolution
        if ($entity->wasChanged('evolution_step_id') && $entity->evolution_step_id && $entity->image) {
            Log::info('EntityObserver: evolution_step_id impostato, chiamo handleTimelineEvolution', [
                'entity_id' => $entity->id,
                'entity_uid' => $entity->uid,
                'evolution_step_id' => $entity->evolution_step_id,
                'image' => $entity->image,
            ]);
            $this->handleTimelineEvolution($entity);
        }
    }

    /**
     * Popola TimelineEvolution, TimelineEvolutionDetail e TimelineEvolutionDetailEntity
     * quando un entity è associato a un evolution_step_id.
     * Copia fisicamente l'immagine da entity_images su time_evolution_details.
     */
    protected function handleTimelineEvolution(Entity $entity): void
    {
        $playerId = $entity->specie->player_id;
        $evolutionStepId = $entity->evolution_step_id;

        // 1. Trova o crea la TimelineEvolution per questo player
        $timelineEvolution = TimelineEvolution::firstOrCreate(
            ['player_id' => $playerId],
            ['uid' => (string) Str::uuid()]
        );

        // 2. Trova o crea il TimelineEvolutionDetail per questo step
        $timelineEvolutionDetail = TimelineEvolutionDetail::where('timeline_evolution_id', $timelineEvolution->id)
            ->where('evolution_step_id', $evolutionStepId)
            ->first();

        if ($timelineEvolutionDetail) {
            // Incrementa population_life se esiste già
            $timelineEvolutionDetail->increment('population_life');
            $timelineEvolutionDetail->refresh();

            // Popola image se mancante e l'entity ne ha una
            Log::info('EntityObserver: Controllo copia immagine (detail esistente)', [
                'detail_image' => $timelineEvolutionDetail->image,
                'entity_image' => $entity->image,
                'entity_image_exists' => $entity->image ? Storage::disk('entity_images')->exists($entity->image) : false,
            ]);

            if (!$timelineEvolutionDetail->image && $entity->image && Storage::disk('entity_images')->exists($entity->image)) {
                $imageContents = Storage::disk('entity_images')->get($entity->image);
                $extension = pathinfo($entity->image, PATHINFO_EXTENSION) ?: 'png';
                $filename = $timelineEvolutionDetail->id . '.' . $extension;
                $path = $timelineEvolution->id . '/' . $filename;

                Storage::disk('time_evolution_details')->put($path, $imageContents);

                $timelineEvolutionDetail->update(['image' => $path]);

                Log::info('EntityObserver: Immagine copiata (detail esistente)', [
                    'path' => $path,
                ]);
            }
        } else {
            // Crea nuovo detail (senza immagine per ora, così abbiamo l'id per il nome file)
            $timelineEvolutionDetail = TimelineEvolutionDetail::create([
                'timeline_evolution_id' => $timelineEvolution->id,
                'evolution_step_id' => $evolutionStepId,
                'image' => null,
                'creation_date' => now(),
                'population_life' => 1,
                'population_death' => 0,
            ]);

            // Copia l'immagine dell'entity da entity_images su time_evolution_details
            // Organizzata in cartella per id di timeline_evolution
            Log::info('EntityObserver: Controllo copia immagine (nuovo detail)', [
                'entity_image' => $entity->image,
                'entity_image_exists' => $entity->image ? Storage::disk('entity_images')->exists($entity->image) : false,
            ]);

            if ($entity->image && Storage::disk('entity_images')->exists($entity->image)) {
                $imageContents = Storage::disk('entity_images')->get($entity->image);
                $extension = pathinfo($entity->image, PATHINFO_EXTENSION) ?: 'png';
                $filename = $timelineEvolutionDetail->id . '.' . $extension;
                $path = $timelineEvolution->id . '/' . $filename;

                Storage::disk('time_evolution_details')->put($path, $imageContents);

                $timelineEvolutionDetail->update(['image' => $path]);

                Log::info('EntityObserver: Immagine copiata (nuovo detail)', [
                    'path' => $path,
                ]);
            } else {
                Log::warning('EntityObserver: Immagine non copiata - condizione fallita', [
                    'entity_image' => $entity->image,
                    'entity_image_exists' => $entity->image ? Storage::disk('entity_images')->exists($entity->image) : false,
                ]);
            }
        }

        Log::info('EntityObserver: TimelineEvolutionDetail popolato', [
            'entity_id' => $entity->id,
            'entity_uid' => $entity->uid,
            'timeline_evolution_id' => $timelineEvolution->id,
            'timeline_evolution_detail_id' => $timelineEvolutionDetail->id,
            'evolution_step_id' => $evolutionStepId,
            'player_id' => $playerId,
            'population_life' => $timelineEvolutionDetail->population_life,
            'population_death' => $timelineEvolutionDetail->population_death,
            'image' => $timelineEvolutionDetail->image,
        ]);

        // 3. Crea il legame TimelineEvolutionDetailEntity
        TimelineEvolutionDetailEntity::create([
            'timeline_evolution_detail_id' => $timelineEvolutionDetail->id,
            'entity_id' => $entity->id,
        ]);

        Log::info('EntityObserver: TimelineEvolutionDetailEntity creato', [
            'entity_id' => $entity->id,
            'entity_uid' => $entity->uid,
            'timeline_evolution_detail_id' => $timelineEvolutionDetail->id,
        ]);
    }

    /**
     * Incrementa population_death e decrementa population_life su TimelineEvolutionDetail quando un entity muore.
     * Non tocca TimelineEvolutionDetailEntity.
     */
    protected function decrementTimelineEvolutionDetail(Entity $entity): void
    {
        if (!$entity->evolution_step_id) {
            return;
        }

        $playerId = $entity->specie->player_id;

        $timelineEvolution = TimelineEvolution::where('player_id', $playerId)->first();
        if (!$timelineEvolution) {
            return;
        }

        $timelineEvolutionDetail = TimelineEvolutionDetail::where('timeline_evolution_id', $timelineEvolution->id)
            ->where('evolution_step_id', $entity->evolution_step_id)
            ->first();

        if (!$timelineEvolutionDetail) {
            return;
        }

        $timelineEvolutionDetail->increment('population_death');
        $timelineEvolutionDetail->decrement('population_life');
        $timelineEvolutionDetail->refresh();

        Log::info('EntityObserver: population_death incrementata e population_life decrementata su TimelineEvolutionDetail per morte entity', [
            'entity_id' => $entity->id,
            'entity_uid' => $entity->uid,
            'timeline_evolution_detail_id' => $timelineEvolutionDetail->id,
            'population_life' => $timelineEvolutionDetail->population_life,
            'population_death' => $timelineEvolutionDetail->population_death,
        ]);
    }
}
