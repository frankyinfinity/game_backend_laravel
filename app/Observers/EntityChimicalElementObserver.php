<?php

namespace App\Observers;

use App\Models\EntityChimicalElement;

class EntityChimicalElementObserver
{
    public function updated(EntityChimicalElement $entityChimicalElement): void
    {
        if ($entityChimicalElement->isDirty('value')) {
            $oldValue = $entityChimicalElement->getOriginal('value');
            $newValue = $entityChimicalElement->value;
            \Log::info('EntityChimicalElement updated: entity_id=' . $entityChimicalElement->entity_id . ', old_value=' . $oldValue . ', new_value=' . $newValue);
        }
    }
}
