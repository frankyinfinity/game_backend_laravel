<?php

namespace App\Custom\Draw\Complex;

use App\Models\Element;
use App\Models\ElementHasPosition;
use App\Custom\Draw\Primitive\Image;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Draw\Primitive\BasicDraw;
use App\Helper\Helper;
use Illuminate\Support\Str;

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

        // Interactivity
        $jsPathClickPanel = resource_path('js/function/common/click_panel.blade.php');
        $jsContentClickPanel = file_get_contents($jsPathClickPanel);
        $jsContentClickPanel = Helper::setCommonJsCode($jsContentClickPanel, Str::random(20));
        $image->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsContentClickPanel);

        // Panel
        $panelX = $x + (Helper::TILE_SIZE / 2);
        $panelY = $y + (Helper::TILE_SIZE / 2);

        $panel = new Rectangle($uid . '_panel');
        $panel->setOrigin($panelX, $panelY);
        $panel->setSize(200, 50);
        $panel->setColor(0xFFFFFF);
        $panel->setRenderable(false);

        // Text (Name)
        $text = new Text($uid . '_text_name');
        $text->setOrigin($panelX + 10, $panelY + 10);
        $text->setText($this->element->name);
        $text->setFontSize(20);
        $text->setRenderable(false);

        $panel->addChild($text);

        $this->drawItems[] = $image->buildJson();
        $this->drawItems[] = $panel->buildJson();
        $this->drawItems[] = $text->buildJson();

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
