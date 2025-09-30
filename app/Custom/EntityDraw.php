<?php

namespace App\Custom;

use App\Helper\Helper;
use App\Models\Entity;
use App\Models\Gene;
use App\Custom\ButtonDraw;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EntityDraw
{

    private Entity $dbEntity;
    private Square $square;
    private array $items;
    public function getItems(): array
    {
        return $this->items;
    }

    public function __construct(Entity $dbEntity, Square $square) {
        $this->dbEntity = $dbEntity;
        $this->square = $square;
        $this->items = [];
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
        $formattedColor = "0x" . strtoupper($hexColorString);

        return $formattedColor;

    }

    private function build() {

        $dbEntity = $this->dbEntity;
        $square = $this->square;
        $size = Helper::getTileSize();

        $centerSquare = $square->getCenter();

        //Body
        $circle = New Circle($dbEntity->uid);
        $circle->setOrigin($centerSquare['x'], y: $centerSquare['y']);
        $circle->setRadius($size / 3);

        $formattedColor = $this->getColor();
        $circle->setColor($formattedColor);

        $jsPathClickEntity = resource_path('js/function/entity/click_entity.blade.php');
        $jsContentClickEntity = file_get_contents($jsPathClickEntity);
        $jsContentClickEntity = Helper::setCommonJsCode($jsContentClickEntity, Str::random(20));

        $circle->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsContentClickEntity);
        foreach ($dbEntity->getFieldAttributes() as $key => $value) {
            $circle->addAttributes($key, $value);
        }

        //Panel
        $panelX = ($dbEntity->specie->player->birthRegion->width*$size) + ($size / 2);
        $panelY = 25;

        $panel = new Rectangle($dbEntity->uid.'_panel');
        $panel->setOrigin($panelX, y: $panelY);
        $panel->setSize(600, 250);
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

        $urlMovement = route('players.entity.movement');
        $urlMovement = str_replace('localhost', 'localhost:8082', $urlMovement);

        $jsMovPaths = [];
        $directions = ['up', 'left', 'down', 'right'];
        foreach ($directions as $direction) {

            $jsPathMov = resource_path('js/function/entity/movement.blade.php');
            $jsContentMov = file_get_contents($jsPathMov);
            $jsContentMov = Helper::setCommonJsCode($jsContentMov, Str::random(20));

            $jsContentMov = str_replace('__url__', $urlMovement, $jsContentMov);
            $jsContentMov = str_replace('__action__', $direction, $jsContentMov);
            $jsContentMov = str_replace('__uid__', $this->dbEntity->uid, $jsContentMov);
            $jsContentMov = str_replace('__player_id__', $player_id, $jsContentMov);

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
        $upButton->build();

        //Left
        $panelX += $sizeButton * 2;
        $leftButton = new ButtonDraw($dbEntity->uid.'_button_left');
        $leftButton->setSize($sizeButton, $sizeButton);
        $leftButton->setOrigin($panelX, $panelY);
        $leftButton->setString('←');
        $leftButton->setColorButton($colorButton);
        $leftButton->setColorString($colorString);
        $leftButton->setTextFontSize(22);
        $leftButton->setOnClick($jsMovPaths['left']);
        $leftButton->build();

        //Down
        $panelX += $sizeButton * 2;
        $downButton = new ButtonDraw($dbEntity->uid.'_button_down');
        $downButton->setSize($sizeButton, $sizeButton);
        $downButton->setOrigin($panelX, $panelY);
        $downButton->setString('↓');
        $downButton->setColorButton($colorButton);
        $downButton->setColorString($colorString);
        $downButton->setTextFontSize(22);
        $downButton->setOnClick($jsMovPaths['down']);
        $downButton->build();

        //Right
        $panelX += $sizeButton * 2;
        $rightButton = new ButtonDraw($dbEntity->uid.'_button_right');
        $rightButton->setSize($sizeButton, $sizeButton);
        $rightButton->setOrigin($panelX, $panelY);
        $rightButton->setString('→');
        $rightButton->setColorButton($colorButton);
        $rightButton->setColorString($colorString);
        $rightButton->setTextFontSize(22);
        $rightButton->setOnClick($jsMovPaths['right']);
        $rightButton->build();

        //Set Children (Panel)
        $panel->addChild($text1->getUid());
        $panel->addChild($text2->getUid());
        foreach ($upButton->getItems() as $item) {$panel->addChild($item->getUid());}
        foreach ($leftButton->getItems() as $item) {$panel->addChild($item->getUid());}
        foreach ($downButton->getItems() as $item) {$panel->addChild($item->getUid());}
        foreach ($rightButton->getItems() as $item) {$panel->addChild($item->getUid());}

        //Get JSON
        $this->items[] = $circle->buildJson();
        $this->items[] = $panel->buildJson();
        $this->items[] = $text1->buildJson();
        $this->items[] = $text2->buildJson();
        foreach ($upButton->getItems() as $item) {$this->items[] = $item->buildJson();}
        foreach ($leftButton->getItems() as $item) {$this->items[] = $item->buildJson();}
        foreach ($downButton->getItems() as $item) {$this->items[] = $item->buildJson();}
        foreach ($rightButton->getItems() as $item) {$this->items[] = $item->buildJson();}

    }

}
