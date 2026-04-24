<?php

namespace App\Custom\Draw\Complex;

use App\Models\Element;
use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionInformation;
use App\Models\ElementHasInformation;
use App\Models\Container;
use App\Services\DockerContainerService;
use App\Custom\Draw\Primitive\Image;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Complex\Element\BrainPanelDraw;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Draw\Complex\ProgressBarDraw;
use App\Custom\Draw\Complex\BarChimicalElementDraw;
use App\Custom\Draw\Support\ScrollGroup;
use App\Custom\Colors;
use App\Helper\Helper;
use Illuminate\Support\Str;

class ElementDraw
{
    private const PROGRESS_BAR_VERTICAL_STEP = 65;

    private Element $element;
    private ?ElementHasPosition $elementHasPosition;
    private $tileI;
    private $tileJ;
    private $playerId;
    private $sessionId;
    private array $drawItems = [];

    public function __construct(Element $element, $tileI, $tileJ, $playerId, $sessionId, ?ElementHasPosition $elementHasPosition = null)
    {
        $this->element = $element;
        $this->elementHasPosition = $elementHasPosition;
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

        // Save position in Database only when no existing record is provided.
        $elementHasPosition = $this->elementHasPosition;
        if ($elementHasPosition === null) {
            $elementHasPosition = ElementHasPosition::query()->create([
                'player_id' => $this->playerId,
                'session_id' => $this->sessionId,
                'element_id' => $this->element->id,
                'uid' => $uid,
                'tile_i' => $this->tileI,
                'tile_j' => $this->tileJ,
            ]);
        } else {
            // Keep draw uid aligned with existing DB uid.
            $uid = (string) $elementHasPosition->uid;
        }

        // --- Container Retrieval ---
        // The container is always created by ElementHasPositionObserver when the record is created.
        $container = Container::where('parent_type', Container::PARENT_TYPE_ELEMENT_HAS_POSITION)
            ->where('parent_id', $elementHasPosition->id)
            ->first();

        $wsPort = (int) ($container->ws_port ?? 0);
        $playerPort = 0;
        $playerContainer = Container::where('parent_type', Container::PARENT_TYPE_PLAYER)
            ->where('parent_id', $this->playerId)
            ->first();
        if ($playerContainer) {
            $playerPort = (int) ($playerContainer->ws_port ?? 0);
        }
        // ------------------------------

        if ($elementHasPosition) {
            $elementHasPosition->loadMissing([
                'brain.neurons.outgoingLinks.toNeuron',
                'brain.neurons.incomingLinks',
            ]);
        }
        
        $x = ($this->tileJ * Helper::TILE_SIZE) + Helper::MAP_START_X;
        $y = ($this->tileI * Helper::TILE_SIZE) + Helper::MAP_START_Y;

        $image = new Image($uid);
        $image->setSrc($imagePath);
        $image->setOrigin($x, $y);
        $image->setSize(32, 32);
        $image->addAttributes('element_id', (int) $this->element->id);
        $image->addAttributes('uid', $uid);
        $image->addAttributes('i', (int) $this->tileI);
        $image->addAttributes('j', (int) $this->tileJ);
        $image->addAttributes('ws_port', $wsPort);
        $image->addAttributes('player_port', $playerPort);
        $image->addAttributes('is_interactive', $this->element->isInteractive());

        // Interactivity
        $jsPathClickElement = resource_path('js/function/element/click_element.blade.php');
        $jsContentClickElement = file_get_contents($jsPathClickElement);
        
        $gatewayBaseUrl = 'ws://' . (string) config('remote_docker.docker_host_ip') . ':' . (int) config('remote_docker.websocket_gateway_port', 9001) . '/?port=';
        $jsContentClickElement = str_replace('__gateway_base__', $gatewayBaseUrl, $jsContentClickElement);
        $jsContentClickElement = str_replace('__player_port__', $playerPort, $jsContentClickElement);
        $jsContentClickElement = Helper::setCommonJsCode($jsContentClickElement, Str::random(20));
        
        $image->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsContentClickElement);

        // Panel: keep it visually attached to the element, like EntityDraw.
        $imageCenterX = $x + 16;
        $imageCenterY = $y + 16;
        $panelX = $imageCenterX + (Helper::TILE_SIZE / 3);
        $panelY = $imageCenterY + (Helper::TILE_SIZE / 3);

        $panel = new Rectangle($uid . '_panel');
        $panel->setOrigin($panelX, $panelY);
        $panel->setSize(400, 200);
        $panel->setColor(Colors::WHITE);
        $panel->setRenderable(false);
        $image->addChild($panel);

        // Text (Name)
        $text = new Text($uid . '_text_name');
        $text->setOrigin($panelX + 10, $panelY + 10);
        $text->setText($this->element->name);
        $text->setFontSize(20);
        $text->setRenderable(false);

        $panel->addChild($text);

        // Brain panel
        $brainPanelItems = [];
        $brainPanelBottomY = $panelY + 34;
        $positionBrain = $elementHasPosition ? $elementHasPosition->brain : null;
        if ($positionBrain) {
            $brainPanel = new BrainPanelDraw($uid . '_brain_panel');
            $brainPanel->setBrain($positionBrain);
            $brainPanel->setOrigin($panelX + 10, $panelY + 34);
            $brainPanel->setRenderable(false);
            $brainPanel->build();

            $panel->setSize(max(240, $brainPanel->getWidth() + 20), max(200, $brainPanel->getHeight() + 60));
            $brainPanelBottomY = $panelY + 34 + $brainPanel->getHeight();

            foreach ($brainPanel->getDrawItems() as $item) {
                $panel->addChild($item);
                $brainPanelItems[] = $item->buildJson();
            }
        }

        // Progress Bars for Genes (only for interactive elements)
        $geneProgressBarItems = [];
        $geneProgressBarCount = 0;
        if ($this->element->isInteractive()) {
            $geneProgressBarItems = $this->addGeneProgressBars($panel, $panelX, $brainPanelBottomY + 24, $elementHasPosition);
            $geneProgressBarCount = count($geneProgressBarItems) / 3; // Each progress bar has ~3 draw items
        }

        // Chemical Bars (only for interactive elements)
        $chemicalBarItems = [];
        $chemicalBarCount = 0;
        if ($this->element->isInteractive() && $elementHasPosition) {
            $chemicalBarStartY = $brainPanelBottomY + 24 + ($geneProgressBarCount * self::PROGRESS_BAR_VERTICAL_STEP);
            $chemicalBarItems = $this->addChemicalBars($panel, $panelX, $chemicalBarStartY, $elementHasPosition);
            $chemicalBarCount = count($chemicalBarItems) / 7; // Approximate items per chemical bar
        }

        // Attack Button position (after gene progress bars and chemical bars)
        $attackBtnY = $brainPanelBottomY + 24 + (($geneProgressBarCount + $chemicalBarCount) * self::PROGRESS_BAR_VERTICAL_STEP) + 25;
        
        // Consumable Button (encapsulated in function)
        $btnItems = [];
        if ($this->element->isConsumable()) {
            $btnItems = $this->addConsumableButton($panel, $panelX, $brainPanelBottomY + 24, $uid);
        }
        
        // Attack Button (for interactive elements, below progress bars)
        $attackBtnItems = [];
        if ($this->element->isInteractive()) {
            $attackBtnItems = $this->addAttackButton($panel, $panelX, $panelY, $attackBtnY, $uid);
            // Mark button as only visible when both entity and element panels are open
            foreach ($attackBtnItems as $item) {
                $item->addAttributes('requires_entity_focus', true);
            }
        }

        // Final Draw Items Assembly (Parent before Children)
        $this->drawItems[] = $image->buildJson();
        $this->drawItems[] = $panel->buildJson();
        $this->drawItems[] = $text->buildJson();
        foreach ($brainPanelItems as $item) {
            $this->drawItems[] = $item;
        }
        
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

        // Add chemical bar items to draw items
        foreach ($chemicalBarItems as $item) {
            $this->drawItems[] = $item->buildJson();
        }

        $this->drawItems = ScrollGroup::attachMany($this->drawItems, Helper::MAP_SCROLL_GROUP_MAIN);
        $this->attachUidCollectionAttribute($uid);

    }

    private function attachUidCollectionAttribute(string $rootUid): void
    {
        $uids = [];
        foreach ($this->drawItems as $drawItem) {
            $uid = $drawItem['uid'] ?? null;
            if (is_string($uid) && $uid !== '') {
                $uids[] = $uid;
            }
        }
        $uids = array_values(array_unique($uids));
        if (!in_array($rootUid, $uids, true)) {
            array_unshift($uids, $rootUid);
        }

        foreach ($this->drawItems as &$drawItem) {
            if (($drawItem['uid'] ?? null) !== $rootUid) {
                continue;
            }
            if (!isset($drawItem['attributes']) || !is_array($drawItem['attributes'])) {
                $drawItem['attributes'] = [];
            }
            $drawItem['attributes']['uids'] = $uids;
            break;
        }
        unset($drawItem);
    }
    
    /**
     * Add progress bars for genes to the panel
     * 
     * @return array Array of progress bar draw items
     */
    private function addGeneProgressBars(Rectangle $panel, $panelX, $startY, $elementHasPosition): array
    {
        $geneProgressBarItems = [];
        
        $elementHasPositionInformations = ElementHasPositionInformation::query()
            ->where('element_has_position_id', $elementHasPosition->id)
            ->with(['gene', 'elementHasPosition'])
            ->get();
        $informationCount = sizeof($elementHasPositionInformations);
        
        if ($informationCount > 0) {
            // Increase panel height to accommodate progress bars
            $panel->setSize(max(400, $panel->buildJson()['width'] ?? 400), max(200, ($informationCount * 105) + 200));
            
            $progressBarY = $startY; // Start below the brain panel
            
            foreach ($elementHasPositionInformations as $elementHasPositionInformation) {

                $gene = $elementHasPositionInformation->gene;
                $elementHasPosition = $elementHasPositionInformation->elementHasPosition;

                $progressBarUid = $elementHasPosition->uid . '_progress_bar_' . $gene->key;
                
                $progressBar = new ProgressBarDraw($progressBarUid);
                $progressBar->setName($gene->name);
                $progressBar->setValue($elementHasPositionInformation->value); 
                $progressBar->setMin($elementHasPositionInformation->min);
                $progressBar->setMax($elementHasPositionInformation->max);
                $progressBar->setModifier($elementHasPositionInformation->modifier ?? null);
                $progressBar->setBorderColor(Colors::LIGHT_GRAY);
                $progressBar->setBarColor(Colors::RED);
                $progressBar->setSize(380, 20);
                $progressBar->setOrigin($panelX + 10, $progressBarY);
                $progressBar->setRenderable(false);
                
                $progressBar->build();
                foreach ($progressBar->getDrawItems() as $item) {
                    $panel->addChild($item);
                    $geneProgressBarItems[] = $item;
                }
                
                $progressBarY += self::PROGRESS_BAR_VERTICAL_STEP; // Space between progress bars
            }
        }
        
        return $geneProgressBarItems;
    }

    /**
     * Add chemical bars to the panel
     * 
     * @return array Array of chemical bar draw items
     */
    private function addChemicalBars(Rectangle $panel, $panelX, $startY, $elementHasPosition): array
    {
        $chemicalBarItems = [];
        
        $elementHasPositionChimicalElements = $elementHasPosition->chimicalElements()
            ->with(['elementHasPositionRuleChimicalElement.details.effects.gene'])
            ->get();
        
        $chimicalCount = $elementHasPositionChimicalElements->count();
        
        if ($chimicalCount > 0) {
            // Increase panel height to accommodate chemical bars
            $currentHeight = $panel->buildJson()['height'] ?? 200;
            $panel->setSize(max(240, $panel->buildJson()['width'] ?? 240), $currentHeight + ($chimicalCount * self::PROGRESS_BAR_VERTICAL_STEP));
            
            $barY = $startY;
            
            foreach ($elementHasPositionChimicalElements as $chimicalElement) {
                $barDraw = new BarChimicalElementDraw($chimicalElement);
                $barDraw->setWidth(380);
                $barDraw->setOrigin($panelX + 10, $barY + 20); // Add offset for title
                $barDraw->setRenderable(false);
                
                $barDraw->build();
                foreach ($barDraw->getDrawItems() as $item) {
                    $panel->addChild($item);
                    $chemicalBarItems[] = $item;
                }
                
                $barY += self::PROGRESS_BAR_VERTICAL_STEP;
            }
        }
        
        return $chemicalBarItems;
    }
    
    /**
     * Add consumable button to the panel
     * 
     * @return array Array of draw items from the button
     */
    private function addConsumableButton(Rectangle $panel, $panelX, $startY, $uid): array
    {
        $panel->setSize(max(240, $panel->buildJson()['width'] ?? 240), 300); // Increase height for brain panel + button
        
        $btnX = $panelX + 10;
        $btnY = $startY;
        
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
    private function addAttackButton(Rectangle $panel, $panelX, $panelY, $attackBtnY, $uid): array
    {
        // Ensure enough room for the button after all progress bars.
        $panelHeight = max(220, ($attackBtnY + 95) - $panelY);
        $panel->setSize(max(240, $panel->buildJson()['width'] ?? 240), $panelHeight);
        
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
