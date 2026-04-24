<?php

namespace App\Observers;

use App\Models\ElementHasPositionChimicalElement;
use App\Models\ElementModifier;
use App\Models\ElementHasPositionRuleChimicalElementDetailEffect;
use Illuminate\Support\Facades\Log;

class ElementHasPositionChimicalElementObserver
{
    public function updated(ElementHasPositionChimicalElement $elementHasPositionChimicalElement): void
    {
        if ($elementHasPositionChimicalElement->isDirty('value')) {
            $oldValue = $elementHasPositionChimicalElement->getOriginal('value');
            $newValue = $elementHasPositionChimicalElement->value;

            Log::info('ElementHasPositionChimicalElement updated: element_has_position_id=' . $elementHasPositionChimicalElement->element_has_position_id . ', old_value=' . $oldValue . ', new_value=' . $newValue);

            $rule = $elementHasPositionChimicalElement->elementHasPositionRuleChimicalElement;
            if (!$rule) {
                return;
            }

            $elementHasPosition = $elementHasPositionChimicalElement->elementHasPosition;
            if (!$elementHasPosition) {
                return;
            }

            $details = $rule->details()->with('effects.gene')->orderBy('min')->get();

            $activeFixedEffectIds = [];
            $allFixedEffectIds = [];
            $activeTimedEffectIds = [];
            $allTimedEffectIds = [];

            foreach ($details as $detail) {
                foreach ($detail->effects as $effect) {
                    if ($effect->type === ElementHasPositionRuleChimicalElementDetailEffect::TYPE_FIXED) {
                        $allFixedEffectIds[] = $effect->id;
                    } elseif ($effect->type === ElementHasPositionRuleChimicalElementDetailEffect::TYPE_TIMED) {
                        $allTimedEffectIds[] = $effect->id;
                    }
                }

                if ($newValue >= $detail->min && $newValue <= $detail->max) {
                    foreach ($detail->effects as $effect) {
                        $informationId = $elementHasPosition->informations()->where('gene_id', $effect->gene_id)->first()?->id;

                        if ($effect->type === ElementHasPositionRuleChimicalElementDetailEffect::TYPE_FIXED) {
                            $activeFixedEffectIds[] = $effect->id;
                        } elseif ($effect->type === ElementHasPositionRuleChimicalElementDetailEffect::TYPE_TIMED) {
                            $activeTimedEffectIds[] = $effect->id;
                        }

                        $exists = ElementModifier::query()
                            ->where('element_has_position_id', $elementHasPosition->id)
                            ->where('effect_id', $effect->id)
                            ->where('element_has_position_information_id', $informationId)
                            ->exists();

                        if (!$exists) {
                            $fields = [
                                'element_has_position_id' => $elementHasPosition->id,
                                'effect_id' => $effect->id,
                                'element_has_position_information_id' => $informationId,
                            ];
                            if ($effect->type === ElementHasPositionRuleChimicalElementDetailEffect::TYPE_TIMED) {
                                if ($effect->duration !== null) {
                                    $fields['finished_at'] = now()->addMinutes($effect->duration);
                                }
                            }

                            ElementModifier::create($fields);
                        }
                    }
                }
            }

            $informationIds = $elementHasPosition->informations()->pluck('id')->toArray();

            if (!empty($allFixedEffectIds)) {
                $toDelete = array_diff($allFixedEffectIds, $activeFixedEffectIds);
                if (!empty($toDelete)) {
                    $modifiersToDelete = ElementModifier::query()
                        ->where('element_has_position_id', $elementHasPosition->id)
                        ->whereIn('element_has_position_information_id', $informationIds)
                        ->whereIn('effect_id', $toDelete)
                        ->get();
                    /** @var ElementModifier $modifier */
                    foreach ($modifiersToDelete as $modifier) {
                        $modifier->delete();
                    }
                }
            }

            if (!empty($allTimedEffectIds)) {
                $toDeleteTimed = array_diff($allTimedEffectIds, $activeTimedEffectIds);
                if (!empty($toDeleteTimed)) {
                    $timedToDelete = ElementModifier::query()
                        ->where('element_has_position_id', $elementHasPosition->id)
                        ->whereIn('element_has_position_information_id', $informationIds)
                        ->whereIn('effect_id', $toDeleteTimed)
                        ->get();
                    /** @var ElementModifier $modifier */
                    foreach ($timedToDelete as $modifier) {
                        $modifier->delete();
                    }
                }
            }
        }
    }
}
