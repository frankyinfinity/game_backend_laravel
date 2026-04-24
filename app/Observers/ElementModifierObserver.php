<?php

namespace App\Observers;

use App\Models\ElementModifier;
use Illuminate\Support\Facades\Log;

class ElementModifierObserver
{
    public function created(ElementModifier $elementModifier): void
    {
        Log::info('[ElementModifierObserver] created start', ['element_modifier_id' => $elementModifier->id]);

        $effect = $elementModifier->effect;
        if (!$effect) {
            Log::warning('[ElementModifierObserver] no effect found');
            return;
        }

        $elementInfo = $elementModifier->elementHasPositionInformation;
        if (!$elementInfo) {
            Log::warning('[ElementModifierObserver] no elementInfo found');
            return;
        }

        Log::info('[ElementModifierObserver] elementInfo found', [
            'elementInfo_id' => $elementInfo->id,
            'min' => $elementInfo->min,
            'max' => $elementInfo->max,
            'modifier' => $elementInfo->modifier
        ]);

        $currentModifier = $elementInfo->modifier ?? 0;
        $newModifier = $currentModifier + $effect->value;

        $currentValue = $elementInfo->value;
        $max = $elementInfo->max + $newModifier;

        if ($currentValue > $max) {
            $elementInfo->value = $max;
        }

        $elementInfo->modifier = $newModifier;
        $elementInfo->save();
        Log::info('[ElementModifierObserver] saved', ['modifier' => $elementInfo->modifier]);
    }

    public function deleting(ElementModifier $elementModifier): void
    {
        Log::info('[ElementModifierObserver] deleting start', ['element_modifier_id' => $elementModifier->id]);

        $effect = $elementModifier->effect;
        if (!$effect) {
            Log::warning('[ElementModifierObserver] no effect found on delete');
            return;
        }

        $elementInfo = $elementModifier->elementHasPositionInformation;
        if (!$elementInfo) {
            Log::warning('[ElementModifierObserver] no elementInfo found on delete');
            return;
        }

        Log::info('[ElementModifierObserver] elementInfo found on delete', [
            'elementInfo_id' => $elementInfo->id,
            'min' => $elementInfo->min,
            'max' => $elementInfo->max,
            'modifier' => $elementInfo->modifier
        ]);

        $currentModifier = $elementInfo->modifier ?? 0;
        $newModifier = $currentModifier - $effect->value;

        $currentValue = $elementInfo->value;
        $max = $elementInfo->max + $newModifier;

        if ($currentValue > $max) {
            $elementInfo->value = $max;
        }

        $elementInfo->modifier = $newModifier;
        $elementInfo->save();
        Log::info('[ElementModifierObserver] saved', ['modifier' => $elementInfo->modifier]);
    }
}
