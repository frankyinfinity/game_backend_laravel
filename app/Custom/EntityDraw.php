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

        $circle = New Circle($dbEntity->uid);
        $circle->setOrigin($centerSquare['x'], y: $centerSquare['y']);
        $circle->setRadius($size / 3);

        $formattedColor = $this->getColor();
        $circle->setColor($formattedColor);

        $functionString = "function test() {
            console.log('UID:');
            console.log(object['uid']);
        };
        test();";

        $circle->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $functionString);

        $this->items[] = $circle->buildJson();

    }

    public function getItems() {
        return $this->items;
    }

}
