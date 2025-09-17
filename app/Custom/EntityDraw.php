<?php

namespace App\Custom;

use App\Helper\Helper;
use App\Models\Entity;
use App\Models\Gene;
use App\Custom\ButtonDraw;

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

        $functionDownString = "function actionDown() {

            let entity_uid = object['uid'];
            let renderable = shapes[entity_uid+'_panel'].renderable;

            //Close all
            const objectPanels = Object.entries(objects).filter(([key, _]) => key.endsWith('_panel')).reduce((obj, [key, value]) => {obj[key] = value;return obj;}, {});
            for (const [key, objectPanel] of Object.entries(objectPanels)) {
                let children = objectPanel['children'];
                for (const [key, childUid] of Object.entries(children)) {
                   let shape = shapes[childUid];
                   shape.renderable = false;
                }
            }

            //Assign Text
            let text2 = shapes[entity_uid+'_text_row_2'];
            text2.text = 'I: ' + object['attributes']['tile_i'];

            let text3 = shapes[entity_uid+'_text_row_3'];
            text3.text = 'J: ' + object['attributes']['tile_j'];

            //Open Panel
            let shapePanel = shapes[entity_uid+'_panel'];
            shapePanel.renderable = !renderable;
            shapePanel.zIndex = 10000;

            //Open Panel (Children)
            let panelChildren = objects[entity_uid+'_panel']['children'];
            for (const [key, childUid] of Object.entries(panelChildren)) {
                let shape = shapes[childUid];
                shape.renderable = !renderable;
                shape.zIndex = 10000;
            }

        };
        actionDown();";
        $circle->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $functionDownString);
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
        $text1->setOrigin($panelX, $panelY);
        $text1->setText("UID: " . $dbEntity->uid);
        $text1->setRenderable(false);

        $panelY += 35;
        $text2 = new Text($dbEntity->uid.'_text_row_2');
        $text2->setOrigin($panelX, $panelY);
        $text2->setText("");
        $text2->setRenderable(false);

        $panelY += 35;
        $text3 = new Text($dbEntity->uid.'_text_row_3');
        $text3->setOrigin($panelX, $panelY);
        $text3->setText("");
        $text3->setRenderable(false);

        //Buttons
        $sizeButton = $size * 0.7;
        $colorButton = 0x0000FF;
        $colorString = 0xFFFFFF;

        $functionUpButton = "function buttonUp() {
            console.log('up');
        };
        buttonUp();";
        $functionLeftButton = "function buttonLeft() {
            console.log('left');
        };
        buttonLeft();";
        $functionDownButton = "function buttonDown() {
            console.log('down');
        };
        buttonDown();";
        $functionRightButton = "function buttonRight() {
            console.log('right');
        };
        buttonRight();";

        //Up
        $panelY += 50;
        $upButton = new ButtonDraw($dbEntity->uid.'_button_up');
        $upButton->setSize($sizeButton, $sizeButton);
        $upButton->setOrigin($panelX, $panelY);
        $upButton->setString('^');
        $upButton->setColorButton($colorButton);
        $upButton->setColorString($colorString);
        $upButton->setOnClick($functionUpButton);
        $upButton->build();

        //Left
        $panelX += $sizeButton * 2;
        $leftButton = new ButtonDraw($dbEntity->uid.'_button_left');
        $leftButton->setSize($sizeButton, $sizeButton);
        $leftButton->setOrigin($panelX, $panelY);
        $leftButton->setString('<');
        $leftButton->setColorButton($colorButton);
        $leftButton->setColorString($colorString);
        $leftButton->setOnClick($functionLeftButton);
        $leftButton->build();

        //Down
        $panelX += $sizeButton * 2;
        $downButton = new ButtonDraw($dbEntity->uid.'_button_down');
        $downButton->setSize($sizeButton, $sizeButton);
        $downButton->setOrigin($panelX, $panelY);
        $downButton->setString('v');
        $downButton->setColorButton($colorButton);
        $downButton->setColorString($colorString);
        $downButton->setOnClick($functionDownButton);
        $downButton->build();

        //Right
        $panelX += $sizeButton * 2;
        $rightButton = new ButtonDraw($dbEntity->uid.'_button_right');
        $rightButton->setSize($sizeButton, $sizeButton);
        $rightButton->setOrigin($panelX, $panelY);
        $rightButton->setString('>');
        $rightButton->setColorButton($colorButton);
        $rightButton->setColorString($colorString);
        $rightButton->setOnClick($functionRightButton);
        $rightButton->build();

        //Set Children (Panel)
        $panel->addChild($text1->getUid());
        $panel->addChild($text2->getUid());
        $panel->addChild($text3->getUid());
        foreach ($upButton->getItems() as $item) {$panel->addChild($item->getUid());}
        foreach ($leftButton->getItems() as $item) {$panel->addChild($item->getUid());}
        foreach ($downButton->getItems() as $item) {$panel->addChild($item->getUid());}
        foreach ($rightButton->getItems() as $item) {$panel->addChild($item->getUid());}

        //Get JSON
        $this->items[] = $circle->buildJson();
        $this->items[] = $panel->buildJson();
        $this->items[] = $text1->buildJson();
        $this->items[] = $text2->buildJson();
        $this->items[] = $text3->buildJson();
        foreach ($upButton->getItems() as $item) {$this->items[] = $item->buildJson();}
        foreach ($leftButton->getItems() as $item) {$this->items[] = $item->buildJson();}
        foreach ($downButton->getItems() as $item) {$this->items[] = $item->buildJson();}
        foreach ($rightButton->getItems() as $item) {$this->items[] = $item->buildJson();}

    }

}
