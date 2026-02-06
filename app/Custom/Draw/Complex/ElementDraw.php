<?php

namespace App\Custom\Draw\Complex;

use App\Models\Element;
use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionInformation;
use App\Models\ElementHasInformation;
use App\Custom\Draw\Primitive\Image;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Draw\Complex\ProgressBarDraw;
use App\Custom\Colors;
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

        // Save position in Database
        $elementHasPosition = ElementHasPosition::query()->create([
            'player_id' => $this->playerId,
            'session_id' => $this->sessionId,
            'element_id' => $this->element->id,
            'uid' => $uid,
            'tile_i' => $this->tileI,
            'tile_j' => $this->tileJ,
        ]);
        
        $x = $this->tileJ * Helper::TILE_SIZE;
        $y = $this->tileI * Helper::TILE_SIZE;

        $image = new Image($uid);
        $image->setSrc($imagePath);
        $image->setOrigin($x, $y);
        $image->setSize(32, 32);

        // Interactivity
        $jsPathClickElement = resource_path('js/function/element/click_element.blade.php');
        $jsContentClickElement = file_get_contents($jsPathClickElement);
        $jsContentClickElement = Helper::setCommonJsCode($jsContentClickElement, Str::random(20));
        $image->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsContentClickElement);

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

        // Progress Bars for Genes (only for interactive elements)
        $geneProgressBarItems = [];
        $geneProgressBarCount = 0;
        if ($this->element->isInteractive()) {
            $geneProgressBarItems = $this->addGeneProgressBars($panel, $panelX, $panelY, $elementHasPosition);
            $geneProgressBarCount = count($geneProgressBarItems) / 3; // Each progress bar has ~3 draw items
        }

        // Attack Button position (after gene progress bars)
        $attackBtnY = $panelY + 60 + ($geneProgressBarCount * 30) + 20;
        
        // Consumable Button (encapsulated in function)
        $btnItems = [];
        if ($this->element->isConsumable()) {
            $btnItems = $this->addConsumableButton($panel, $panelX, $panelY, $uid);
        }
        
        // Attack Button (for interactive elements, below progress bars)
        $attackBtnItems = [];
        if ($this->element->isInteractive()) {
            $attackBtnItems = $this->addAttackButton($panel, $panelX, $attackBtnY, $uid);
            // Mark button as only visible when both entity and element panels are open
            foreach ($attackBtnItems as $item) {
                $item->addAttributes('requires_entity_focus', true);
            }
        }

        // Final Draw Items Assembly (Parent before Children)
        $this->drawItems[] = $image->buildJson();
        $this->drawItems[] = $panel->buildJson();
        $this->drawItems[] = $text->buildJson();
        
        foreach ($btnItems as $item) {
            $this->drawItems[] = $item->buildJson();
        }
        
        foreach ($attackBtnItems as $item) {
            $this->drawItems[] = $item->buildJson();
        }
        
        // Add gene progress bar items to draw items
        foreach ($geneProgressBarItems as $item) {
            $this->drawItems[] = $item->buildJson();
        }

    }
    
    /**
     * Add progress bars for genes to the panel
     * 
     * @return array Array of progress bar draw items
     */
    private function addGeneProgressBars(Rectangle $panel, $panelX, $panelY, $elementHasPosition): array
    {
        $geneProgressBarItems = [];
        
        $elementHasPositionInformations = ElementHasPositionInformation::query()
            ->where('element_has_position_id', $elementHasPosition->id)
            ->with(['gene', 'elementHasPosition'])
            ->get();
        $informationCount = sizeof($elementHasPositionInformations);
        
        if ($informationCount > 0) {
            // Increase panel height to accommodate progress bars
            $panel->setSize(200, 50 + ($informationCount * 60));
            
            $progressBarY = $panelY + 60; // Start below the name text
            
            foreach ($elementHasPositionInformations as $elementHasPositionInformation) {

                $gene = $elementHasPositionInformation->gene;
                $elementHasPosition = $elementHasPositionInformation->elementHasPosition;

                $progressBarUid = 'gene_progress_' . $gene->key . '_element_' . $elementHasPosition->uid;
                
                $progressBar = new ProgressBarDraw($progressBarUid);
                $progressBar->setName($gene->name);
                $progressBar->setValue($elementHasPositionInformation->value); 
                $progressBar->setMin($elementHasPositionInformation->min);
                $progressBar->setMax($elementHasPositionInformation->max);
                $progressBar->setBorderColor(Colors::LIGHT_GRAY);
                $progressBar->setBarColor(Colors::RED);
                $progressBar->setSize(180, 20);
                $progressBar->setOrigin($panelX + 10, $progressBarY);
                $progressBar->setRenderable(false);
                
                $progressBar->build();
                foreach ($progressBar->getDrawItems() as $item) {
                    $panel->addChild($item);
                    $geneProgressBarItems[] = $item;
                }
                
                $progressBarY += 30; // Space between progress bars
            }
        }
        
        return $geneProgressBarItems;
    }
    
    /**
     * Add consumable button to the panel
     * 
     * @return array Array of draw items from the button
     */
    private function addConsumableButton(Rectangle $panel, $panelX, $panelY, $uid): array
    {
        $panel->setSize(200, 120); // Increase height for button
        
        $btnX = $panelX + 10;
        $btnY = $panelY + 50;
        
        $jsPathConsume = resource_path('js/function/element/consume.blade.php');
        $jsContentConsume = file_get_contents($jsPathConsume);
        $jsContentConsume = Helper::setCommonJsCode($jsContentConsume, Str::random(20));
        
        $btn = new ButtonDraw($uid . '_btn_consume');
        $btn->setOrigin($btnX, $btnY);
        $btn->setSize(180, 50);
        $btn->setString('Consuma');
        $btn->setColorButton(Colors::BLUE);
        $btn->setColorString(Colors::WHITE);
        $btn->setRenderable(false);
        $btn->setOnClick($jsContentConsume);
        $btn->build();
        
        $btnItems = [];
        foreach ($btn->getDrawItems() as $item) {
            $panel->addChild($item);
            $btnItems[] = $item;
        }
        
        return $btnItems;
    }
    
    /**
     * Add attack button to the panel (for interactive elements)
     * 
     * @return array Array of draw items from the button
     */
    private function addAttackButton(Rectangle $panel, $panelX, $attackBtnY, $uid): array
    {
        // Increase panel height for button
        $panel->setSize(200, 200);
        
        $btnX = $panelX + 10;
        $btnY = $attackBtnY;
        
        $jsPathAttack = resource_path('js/function/element/attack.blade.php');
        $jsContentAttack = file_get_contents($jsPathAttack);
        $jsContentAttack = Helper::setCommonJsCode($jsContentAttack, Str::random(20));
        
        $btn = new ButtonDraw($uid . '_btn_attack');
        $btn->setOrigin($btnX, $btnY);
        $btn->setSize(180, 50);
        $btn->setString('Attacco');
        $btn->setColorButton(Colors::RED);
        $btn->setColorString(Colors::WHITE);
        $btn->setRenderable(false);
        $btn->setOnClick($jsContentAttack);
        $btn->build();
        
        $btnItems = [];
        foreach ($btn->getDrawItems() as $item) {
            $panel->addChild($item);
            $btnItems[] = $item;
        }
        
        return $btnItems;
    }
}
