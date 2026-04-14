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
        $effect = $playerModifier->playerRuleChimicalElementDetailEffect;
        if (!$effect) {
            return;
        }

        $genome = $playerModifier->genome;
        if (!$genome) {
            return;
        }

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
        $newModifier = $currentModifier + $effect->value;

        $minModifier = $min;
        $maxModifier = $max;

        if ($newModifier > $maxModifier) {
            $newModifier = $maxModifier;
        } elseif ($newModifier < $minModifier) {
            $newModifier = $minModifier;
        }

        $genome->modifier = $newModifier;
        $genome->save();
    }

    public function deleted(PlayerModifier $playerModifier): void
    {
        $effect = $playerModifier->playerRuleChimicalElementDetailEffect;
        if (!$effect) {
            return;
        }

        $genome = $playerModifier->genome;
        if (!$genome) {
            return;
        }

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

        $genome->modifier = $newModifier;
        $genome->save();
    }
}