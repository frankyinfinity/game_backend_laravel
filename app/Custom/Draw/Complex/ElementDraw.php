<?php

namespace App\Custom\Draw\Complex;

use App\Models\Element;
use App\Models\ElementHasPosition;
use App\Custom\Draw\Primitive\Image;
use App\Helper\Helper;

class ElementDraw
{
    private Element $element;
    private $tileI;
    private $tileJ;
    private $playerId;
    private $sessionId;
    private array $drawItems = [];

    public function __construct(Element $element, $tileI, $tileJ, $playerId, $sessionId)
    {
        $this->element = $element;
        $this->tileI = $tileI;
        $this->tileJ = $tileJ;
        $this->playerId = $playerId;
        $this->sessionId = $sessionId;
        $this->drawItems = [];
        $this->build();
    }

    /**
     * Get the JSON draw items for the element
     * 
     * @return array
     */
    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    /**
     * Build the drawing components for the element
     */
    private function build(): void
    {
        // UNIQUE UID: adds coordinates to avoid overwriting elements in the frontend
        $uid = 'element_' . $this->element->id . '_' . $this->tileI . '_' . $this->tileJ;
        $imagePath = '/storage/elements/' . $this->element->id . '.png';
        
        $x = $this->tileJ * Helper::TILE_SIZE;
        $y = $this->tileI * Helper::TILE_SIZE;

        $image = new Image($uid);
        $image->setSrc($imagePath);
        $image->setOrigin($x, $y);
        $image->setSize(64, 64);

        $this->drawItems[] = $image->buildJson();

        // Save position in Database
        ElementHasPosition::query()->create([
            'player_id' => $this->playerId,
            'session_id' => $this->sessionId,
            'element_id' => $this->element->id,
            'uid' => $uid,
            'tile_i' => $this->tileI,
            'tile_j' => $this->tileJ,
        ]);
    }
}
