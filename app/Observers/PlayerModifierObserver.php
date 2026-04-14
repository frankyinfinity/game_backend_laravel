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

        $currentModifier = $genome->modifier ?? 0;
        $newModifier = $currentModifier + $effect->value;

        $currentValue = $entityInfo->value;
        $max = $genome->max + $newModifier;

        if ($currentValue > $max) {
            $entityInfo->value = $max;
            $entityInfo->save();
        }

        $genome->modifier = $newModifier;
        $genome->save();
        Log::info('[PlayerModifierObserver] saved', ['modifier' => $genome->modifier]);

    }

    public function deleting(PlayerModifier $playerModifier): void
    {
        Log::info('[PlayerModifierObserver] deleting start', ['player_modifier_id' => $playerModifier->id]);

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

        $currentModifier = $genome->modifier ?? 0;
        $newModifier = $currentModifier - $effect->value;

        $currentValue = $entityInfo->value;
        $max = $genome->max + $newModifier;

        if ($currentValue > $max) {
            $entityInfo->value = $max;
            $entityInfo->save();
        }

        $genome->modifier = $newModifier;
        $genome->save();
        Log::info('[PlayerModifierObserver] saved', ['modifier' => $genome->modifier]);

    }
}