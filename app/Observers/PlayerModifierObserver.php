<?php

namespace App\Observers;

use App\Models\PlayerModifier;
use App\Models\PlayerRuleChimicalElementDetailEffect;
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

        $currentModifier = $genome->modifier ?? 0;
        $genome->modifier = $currentModifier + $effect->value;
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

        $currentModifier = $genome->modifier ?? 0;
        $genome->modifier = $currentModifier - $effect->value;
        $genome->save();
    }
}
