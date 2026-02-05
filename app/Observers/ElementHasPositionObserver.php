<?php

namespace App\Observers;

use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionInformation;
use App\Models\ElementHasGene;
use App\Models\ElementInformation;

class ElementHasPositionObserver
{
    /**
     * Handle the ElementHasPosition "created" event.
     */
    public function created(ElementHasPosition $elementHasPosition): void
    {
        if($elementHasPosition->element->isInteractive()) {
            
            $element = $elementHasPosition->element;
            $elementHasInformations = ElementInformation::query()
                ->where('element_id', $element->id)
                ->get();

            foreach ($elementHasInformations as $elementHasInformation) {
                ElementHasPositionInformation::query()->create([
                    'element_has_position_id' => $elementHasPosition->id,
                    'gene_id' => $elementHasInformation->gene_id,
                    'min' => $elementHasInformation->min_value,
                    'max' => $elementHasInformation->value,
                    'value' => $elementHasInformation->value
                ]);
            }

        }
    }

    /**
     * Handle the ElementHasPosition "deleting" event.
     */
    public function deleting(ElementHasPosition $elementHasPosition): void
    {
       ElementHasPositionInformation::query()
            ->where('element_has_position_id', $elementHasPosition->id)
            ->delete();
    }
}
