<?php

namespace App\Observers;

use App\Models\Entity;
use App\Models\EntityChimicalElement;
use App\Models\PlayerRuleChimicalElement;

class EntityObserver
{
    /**
     * Handle the Entity "created" event.
     */
    public function created(Entity $entity): void
    {
        $specie = $entity->specie;
        if (!$specie) {
            return;
        }

        $player = $specie->player;
        if (!$player) {
            return;
        }

        $playerRules = PlayerRuleChimicalElement::where('player_id', $player->id)->get();
        foreach ($playerRules as $playerRule) {
            EntityChimicalElement::query()->create([
                'entity_id' => $entity->id,
                'player_rule_chimical_element_id' => $playerRule->id,
                'value' => $playerRule->max
            ]);
        }
    }

    /**
     * Handle the Entity "updated" event.
     */
    public function updated(Entity $entity): void
    {
        //
    }

    /**
     * Handle the Entity "deleted" event.
     */
    public function deleted(Entity $entity): void
    {
        //
    }
}