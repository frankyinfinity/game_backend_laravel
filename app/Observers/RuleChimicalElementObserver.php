<?php

namespace App\Observers;

use App\Models\RuleChimicalElement;

class RuleChimicalElementObserver
{
    public function saving(RuleChimicalElement $rule)
    {
        $rule->loadMissing(['chimicalElement', 'complexChimicalElement']);

        $elementName = null;
        $symbol = null;

        if ($rule->chimicalElement) {
            $elementName = $rule->chimicalElement->name;
            $symbol = $rule->chimicalElement->symbol;
        } elseif ($rule->complexChimicalElement) {
            $elementName = $rule->complexChimicalElement->name;
            $symbol = $rule->complexChimicalElement->symbol;
        }

        if ($elementName && $symbol) {
            $rule->title = $rule->name . ' [' . $elementName . ' (' . $symbol . ')]';
        } elseif ($elementName) {
            $rule->title = $rule->name . ' [' . $elementName . ']';
        } else {
            $rule->title = $rule->name;
        }
    }
}
