<?php

namespace App\Observers;

use App\Models\PlayerModifier;
use App\Models\PlayerRuleChimicalElementDetailEffect;
use App\Models\EntityInformation;
use Illuminate\Support\Facades\Log;

class PlayerModifierObserver
{
    public function created(PlayerModifier $playerModifier): void
    {
        Log::info('[PlayerModifierObserver] created start', ['player_modifier_id' => $playerModifier->id]);

        $effect = $playerModifier->playerRuleChimicalElementDetailEffect;
        if (!$effect) {
            Log::warning('[PlayerModifierObserver] no effect found');
            return;
        }

        $genome = $playerModifier->genome;
        if (!$genome) {
            Log::warning('[PlayerModifierObserver] no genome found');
            return;
        }

        Log::info('[PlayerModifierObserver] genome found', [
            'genome_id' => $genome->id, 
            'min' => $genome->min, 
            'max' => $genome->max,
            'modifier' => $genome->modifier
        ]);

        $entityInfo = EntityInformation::query()->where('genome_id', $genome->id)->first();
        if (!$entityInfo) {
            Log::warning('[PlayerModifierObserver] no entityInfo found', ['genome_id' => $genome->id]);
            return;
        }

        Log::info('[PlayerModifierObserver] entityInfo found', ['value' => $entityInfo->value]);

        $currentValue = $entityInfo->value;
        $min = $genome->min;
        $max = $genome->max + ($genome->modifier ?? 0);

        if ($currentValue > $max) {
            $entityInfo->value = $max;
            $entityInfo->save();
        }

        $currentModifier = $genome->modifier ?? 0;
        $newModifier = $currentModifier + $effect->value;

        $minModifier = $min;
        $maxModifier = $max;

        if ($newModifier > $maxModifier) {
            $newModifier = $maxModifier;
        } elseif ($newModifier < $minModifier) {
            $newModifier = $minModifier;
        }

        Log::info('[PlayerModifierObserver] setting modifier', [
            'current' => $currentModifier, 
            'effect_value' => $effect->value,
            'new' => $newModifier,
            'min' => $minModifier,
            'max' => $maxModifier
        ]);

        $genome->modifier = $newModifier;
        $genome->save();
        Log::info('[PlayerModifierObserver] saved', ['modifier' => $genome->modifier]);
    }

    public function deleted(PlayerModifier $playerModifier): void
    {
        Log::info('[PlayerModifierObserver] deleted start', ['player_modifier_id' => $playerModifier->id]);

        $effect = $playerModifier->playerRuleChimicalElementDetailEffect;
        if (!$effect) {
            Log::warning('[PlayerModifierObserver] no effect found on delete');
            return;
        }

        $genome = $playerModifier->genome;
        if (!$genome) {
            Log::warning('[PlayerModifierObserver] no genome found on delete');
            return;
        }

        Log::info('[PlayerModifierObserver] genome found on delete', [
            'genome_id' => $genome->id, 
            'min' => $genome->min, 
            'max' => $genome->max,
            'modifier' => $genome->modifier
        ]);

        $entityInfo = EntityInformation::query()->where('genome_id', $genome->id)->first();
        if (!$entityInfo) {
            return;
        }

        $currentValue = $entityInfo->value;
        $min = $genome->min;
        $max = $genome->max + ($genome->modifier ?? 0);

        if ($currentValue > $max) {
            $entityInfo->value = $max;
            $entityInfo->save();
        }

        $currentModifier = $genome->modifier ?? 0;
        $newModifier = $currentModifier - $effect->value;

        $minModifier = $min;
        $maxModifier = $max;

        if ($newModifier > $maxModifier) {
            $newModifier = $maxModifier;
        } elseif ($newModifier < $minModifier) {
            $newModifier = $minModifier;
        }

        Log::info('[PlayerModifierObserver] setting modifier on delete', [
            'current' => $currentModifier, 
            'effect_value' => $effect->value,
            'new' => $newModifier
        ]);

        $genome->modifier = $newModifier;
        $genome->save();
    }
}