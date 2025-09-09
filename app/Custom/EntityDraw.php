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

            //Panel
            let panel = shapes[entity_uid+'_panel'];
            panel.renderable = renderable ? false : true;
            panel.zIndex = 10000;

            //UID
            let text1 = shapes[entity_uid+'_text_row_1'];
            text1.renderable = renderable ? false : true;
            text1.zIndex = 10001;

            //Tile I
            let text2 = shapes[entity_uid+'_text_row_2'];
            text2.text = 'I: ' + object['attributes']['tile_i'];
            text2.renderable = renderable ? false : true;
            text2.zIndex = 10001;

            //Tile J
            let text3 = shapes[entity_uid+'_text_row_3'];
            text3.text = 'J: ' + object['attributes']['tile_j'];
            text3.renderable = renderable ? false : true;
            text3.zIndex = 10001;

        };
        actionDown();";
        $circle->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $functionDownString);
        $circle->addAttributes('tile_i', $dbEntity->tile_i);
        $circle->addAttributes('tile_j', $dbEntity->tile_j);

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

        $this->items[] = $circle->buildJson();
        $this->items[] = $panel->buildJson();
        $this->items[] = $text1->buildJson();
        $this->items[] = $text2->buildJson();
        $this->items[] = $text3->buildJson();

    }

    public function getItems() {
        return $this->items;
    }

}
