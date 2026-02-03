<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Draw\Complex\ProgressBarDraw;
use App\Helper\Helper;
use App\Custom\Colors;
use App\Models\Entity;
use App\Models\Gene;
use App\Models\Container;
use App\Models\EntityInformation;
use Illuminate\Support\Str;

class EntityDraw
{

    private Entity $dbEntity;
    private Square $square;
    private array $drawItems;
    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    public function __construct(Entity $dbEntity, Square $square) {
        $this->dbEntity = $dbEntity;
        $this->square = $square;
        $this->drawItems = [];
        $this->build();
    }
    private function getGenomes() {

        $dbEntity = $this->dbEntity;
        $dbEntity->genomes->map(function ($genome) {
            $genome->gene_key = $genome->gene->key;
            $genome->gene_name = $genome->gene->name;
            $genome->gene_value = $genome->entityInformations[0]->value;
            return $genome;
        });

        return $dbEntity->genomes;

    }

    private function getGene($genomes, $key) {
        $value = 0;
        $genome = collect($genomes)->where('gene_key', $key)->first();
        if ($genome !== null) {
            $value = $genome['gene_value'];
        }
        return $value;
    }

    private function getColor() {

        $genomes = $this->getGenomes();

        $red = $this->getGene($genomes, Gene::KEY_RED_TEXTURE);
        $green = $this->getGene($genomes, Gene::KEY_GREEN_TEXTURE);
        $blue = $this->getGene($genomes, Gene::KEY_BLUE_TEXTURE);

        $rgbDecimal = ($red << 16) | ($green << 8) | $blue;
        $hexColorString = str_pad(dechex($rgbDecimal), 6, '0', STR_PAD_LEFT);
        return "0x" . strtoupper($hexColorString);

    }

    private function build(): void
    {

        $dbEntity = $this->dbEntity;
        $square = $this->square;
        $size = Helper::TILE_SIZE;

        $centerSquare = $square->getCenter();

        //Body
        $circle = New Circle($dbEntity->uid);
        $circle->setOrigin($centerSquare['x'], y: $centerSquare['y']);
        $circle->setRadius($size / 3);

        $formattedColor = $this->getColor();
        $circle->setColor($formattedColor);

        $jsPathClickPanel = resource_path('js/function/common/click_panel.blade.php');
        $jsContentClickPanel = file_get_contents($jsPathClickPanel);
        $jsContentClickPanel = Helper::setCommonJsCode($jsContentClickPanel, Str::random(20));

        $circle->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsContentClickPanel);
        foreach ($dbEntity->getFieldAttributes() as $key => $value) {
            $circle->addAttributes($key, $value);
        }

        //Panel
        $panelX = $centerSquare['x'] + ($size / 3);
        $panelY = $centerSquare['y'] + ($size / 3);

        $panel = new Rectangle($dbEntity->uid.'_panel');
        $panel->setOrigin($panelX, y: $panelY);
        $panel->setSize(400, 345);
        $panel->setColor(0xFFFFFF);
        $panel->setRenderable(false);

        //Text
        $panelX += 10;
        $panelY += 10;
        $text1 = new Text($dbEntity->uid.'_text_row_1');
        $text1->setFontSize(22);
        $text1->setOrigin($panelX, $panelY);
        $text1->setText("UID: " . $dbEntity->uid);
        $text1->setRenderable(false);

        $panelY += 35;
        $text2 = new Text($dbEntity->uid.'_text_row_2');
        $text2->setFontSize(22);
        $text2->setOrigin($panelX, $panelY);
        $text2->setText("I: " . $dbEntity->tile_i . ' - J: ' . $dbEntity->tile_j);
        $text2->setRenderable(false);

        //Buttons
        $sizeButton = $size * 0.7;
        $colorButton = 0x0000FF;
        $colorString = 0xFFFFFF;

        $player_id = $this->dbEntity->specie->player_id;

        //WS Port
        $container = Container::query()
            ->where('parent_type', Container::PARENT_TYPE_ENTITY)
            ->where('parent_id', $this->dbEntity->id)
            ->first();
        $wsPort = $container ? $container->ws_port : null;

        $jsMovPaths = [];
        $directions = ['up', 'left', 'down', 'right'];
        foreach ($directions as $direction) {

            $jsPathMov = resource_path('js/function/entity/movement_ws.blade.php');
            $jsContentMov = file_get_contents($jsPathMov);
            $jsContentMov = Helper::setCommonJsCode($jsContentMov, Str::random(20));

            $jsContentMov = str_replace('__port__', $wsPort, $jsContentMov);
            $jsContentMov = str_replace('__action__', $direction, $jsContentMov);

            $jsMovPaths[$direction] = $jsContentMov;

        }

        //Up
        $panelY += 50;
        $upButton = new ButtonDraw($dbEntity->uid.'_button_up');
        $upButton->setSize($sizeButton, $sizeButton);
        $upButton->setOrigin($panelX, $panelY);
        $upButton->setString('↑');
        $upButton->setColorButton($colorButton);
        $upButton->setColorString($colorString);
        $upButton->setTextFontSize(22);
        $upButton->setOnClick($jsMovPaths['up']);
        $upButton->setRenderable(false);
        $upButton->build();

        //Left
        $panelX += $sizeButton * 1.2;
        $leftButton = new ButtonDraw($dbEntity->uid.'_button_left');
        $leftButton->setSize($sizeButton, $sizeButton);
        $leftButton->setOrigin($panelX, $panelY);
        $leftButton->setString('←');
        $leftButton->setColorButton($colorButton);
        $leftButton->setColorString($colorString);
        $leftButton->setTextFontSize(22);
        $leftButton->setOnClick($jsMovPaths['left']);
        $leftButton->setRenderable(false);
        $leftButton->build();

        //Down
        $panelX += $sizeButton * 1.2;
        $downButton = new ButtonDraw($dbEntity->uid.'_button_down');
        $downButton->setSize($sizeButton, $sizeButton);
        $downButton->setOrigin($panelX, $panelY);
        $downButton->setString('↓');
        $downButton->setColorButton($colorButton);
        $downButton->setColorString($colorString);
        $downButton->setTextFontSize(22);
        $downButton->setOnClick($jsMovPaths['down']);
        $downButton->setRenderable(false);
        $downButton->build();

        //Right
        $panelX += $sizeButton * 1.2;
        $rightButton = new ButtonDraw($dbEntity->uid.'_button_right');
        $rightButton->setSize($sizeButton, $sizeButton);
        $rightButton->setOrigin($panelX, $panelY);
        $rightButton->setString('→');
        $rightButton->setColorButton($colorButton);
        $rightButton->setColorString($colorString);
        $rightButton->setTextFontSize(22);
        $rightButton->setOnClick($jsMovPaths['right']);
        $rightButton->setRenderable(false);
        $rightButton->build();

        //Progress Bar
        $itemBars = [];

        $genomeIds = $dbEntity->genomes->pluck('id')->toArray();
        $entityInformations = EntityInformation::query()
            ->whereIn('genome_id', $genomeIds)
            ->with(['genome'])
            ->get();

        $panelX = $centerSquare['x'] + ($size / 3) + 10; // Reset X to panel start
        $panelY += $sizeButton + 60; // Space after buttons

        foreach($entityInformations as $entityInformation) {
            $genome = $entityInformation->genome;
            $gene = $genome->gene;
            if($gene->type === 'dynamic_max') {
             
                $progressBar = new ProgressBarDraw($dbEntity->uid.'_progress_bar_'.$gene->key);
                $progressBar->setName($gene->name);
                $progressBar->setMin($genome->min);
                $progressBar->setMax($genome->max);
                $progressBar->setValue($entityInformation->value);
                $progressBar->setBorderColor(Colors::LIGHT_GRAY);
                $progressBar->setBarColor(Colors::RED);
                $progressBar->setOrigin($panelX, $panelY);
                $progressBar->setSize(380, 20);
                $progressBar->setRenderable(false);
                $progressBar->build();

                $itemBars[] = $progressBar->getDrawItems();
                $panelY += 60; // Space for the next progress bar (label + bar + range)

            }
        }

        //Set Children (Panel)
        $panel->addChild($text1);
        $panel->addChild($text2);
        foreach ($upButton->getDrawItems() as $item) {$panel->addChild($item);}
        foreach ($leftButton->getDrawItems() as $item) {$panel->addChild($item);}
        foreach ($downButton->getDrawItems() as $item) {$panel->addChild($item);}
        foreach ($rightButton->getDrawItems() as $item) {$panel->addChild($item);}
        foreach ($itemBars as $item) {
            foreach ($item as $item2) {
                $panel->addChild($item2);
            }
        }

        //Get JSON
        $this->drawItems[] = $circle->buildJson();
        $this->drawItems[] = $panel->buildJson();
        $this->drawItems[] = $text1->buildJson();
        $this->drawItems[] = $text2->buildJson();
        foreach ($upButton->getDrawItems() as $item) {$this->drawItems[] = $item->buildJson();}
        foreach ($leftButton->getDrawItems() as $item) {$this->drawItems[] = $item->buildJson();}
        foreach ($downButton->getDrawItems() as $item) {$this->drawItems[] = $item->buildJson();}
        foreach ($rightButton->getDrawItems() as $item) {$this->drawItems[] = $item->buildJson();}
        foreach ($itemBars as $item) {
            foreach ($item as $item2) {
                $this->drawItems[] = $item2->buildJson();
            }
        }

    }

}
