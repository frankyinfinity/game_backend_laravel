<?php

namespace App\Observers;

use App\Models\RuleChimicalElement;

class RuleChimicalElementObserver
{
    public function saving(RuleChimicalElement $rule)
    {
        $rule->loadMissing(['chimicalElement', 'complexChimicalElement']);
        
        if ($rule->chimicalElement) {
            $rule->title = $rule->chimicalElement->name . ' (' . $rule->chimicalElement->symbol . ')';
        } elseif ($rule->complexChimicalElement) {
            $rule->title = $rule->complexChimicalElement->name . ' (' . $rule->complexChimicalElement->symbol . ')';
        }
    }
}