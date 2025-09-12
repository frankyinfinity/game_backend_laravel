<?php

namespace App\Custom;

use App\Helper\Helper;
use App\Models\Entity;
use App\Models\Gene;
use Arr;
use Str;

class EntityDraw
{

    private Entity $dbEntity;
    private Square $square;
    private array $items;

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
            let keyCloses = ['_panel', '_text_row_1', '_text_row_2', '_text_row_3'];
            for (const suffix of keyCloses) {
                 const suffixObjects = Object.entries(objects).filter(([key, _]) => key.endsWith(suffix)).reduce((obj, [key, value]) => {obj[key] = value;return obj;}, {});
                 for (const [key, obj] of Object.entries(suffixObjects)) {
                   let shape = shapes[key];
                   shape.renderable = false;
                 }
            }

            //Assign Text
            let text2 = shapes[entity_uid+'_text_row_2'];
            text2.text = 'I: ' + object['attributes']['tile_i'];

            let text3 = shapes[entity_uid+'_text_row_3'];
            text3.text = 'J: ' + object['attributes']['tile_j'];

            //Open all
            let keyOpens = ['_panel', '_text_row_1', '_text_row_2', '_text_row_3', '_up_button', '_up_button_text',
                '_left_button', '_left_button_text', '_down_button', '_down_button_text',
                '_right_button', '_right_button_text'];
            for (const suffix of keyOpens) {
              let shape = shapes[entity_uid+suffix];
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
        $colorText = 0xFFFFFF;

        //Up
        $panelY += 50;
        $upButton = new Square($dbEntity->uid.'_up_button');
        $upButton->setOrigin($panelX, $panelY);
        $upButton->setSize($sizeButton);
        $upButton->setColor($colorButton);
        $upButton->setRenderable(false);

        $upButtonText = new Text($dbEntity->uid.'_up_button_text');
        $upButtonText->setOrigin($panelX, $panelY);
        $upButtonText->setText("^");
        $upButtonText->setColor($colorText);
        $upButtonText->setRenderable(false);

        //Left
        $panelX += $sizeButton * 2;
        $leftButton = new Square($dbEntity->uid.'_left_button');
        $leftButton->setOrigin($panelX, $panelY);
        $leftButton->setSize($sizeButton);
        $leftButton->setColor($colorButton);
        $leftButton->setRenderable(false);

        $leftButtonText = new Text($dbEntity->uid.'_left_button_text');
        $leftButtonText->setOrigin($panelX, $panelY);
        $leftButtonText->setText("<");
        $leftButtonText->setColor($colorText);
        $leftButtonText->setRenderable(false);

        //Down
        $panelX += $sizeButton * 2;
        $downButton = new Square($dbEntity->uid.'_down_button');
        $downButton->setOrigin($panelX, $panelY);
        $downButton->setSize($sizeButton);
        $downButton->setColor($colorButton);
        $downButton->setRenderable(false);

        $downButtonText = new Text($dbEntity->uid.'_down_button_text');
        $downButtonText->setOrigin($panelX, $panelY);
        $downButtonText->setText("v");
        $downButtonText->setColor($colorText);
        $downButtonText->setRenderable(false);

        //Right
        $panelX += $sizeButton * 2;
        $rightButton = new Square($dbEntity->uid.'_right_button');
        $rightButton->setOrigin($panelX, $panelY);
        $rightButton->setSize($sizeButton);
        $rightButton->setColor($colorButton);
        $rightButton->setRenderable(false);

        $rightButtonText = new Text($dbEntity->uid.'_right_button_text');
        $rightButtonText->setOrigin($panelX, $panelY);
        $rightButtonText->setText(">");
        $rightButtonText->setColor($colorText);
        $rightButtonText->setRenderable(false);

        $this->items[] = $circle->buildJson();
        $this->items[] = $panel->buildJson();
        $this->items[] = $text1->buildJson();
        $this->items[] = $text2->buildJson();
        $this->items[] = $text3->buildJson();
        $this->items[] = $upButton->buildJson();
        $this->items[] = $upButtonText->buildJson();
        $this->items[] = $leftButton->buildJson();
        $this->items[] = $leftButtonText->buildJson();
        $this->items[] = $downButton->buildJson();
        $this->items[] = $downButtonText->buildJson();
        $this->items[] = $rightButton->buildJson();
        $this->items[] = $rightButtonText->buildJson();

    }

    public function getItems() {
        return $this->items;
    }

}
