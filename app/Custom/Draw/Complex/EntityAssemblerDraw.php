<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Helper\Helper;

class EntityAssemblerDraw
{
    private $uid;
    private array $drawItems = [];
    private $borderRadius = 0;

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    public function getDrawItemsWithObjectDraw($sessionId): array
    {
        $drawItems = [];
        foreach ($this->drawItems as $item) {
            $objectDraw = new \App\Custom\Manipulation\ObjectDraw($item->buildJson(), $sessionId);
            $drawItems[] = $objectDraw->get();
        }
        return $drawItems;
    }

    public function setBorderRadius($radius): void
    {
        $this->borderRadius = $radius;
    }

    public function build(): void
    {
        $marginLeft = 20;
        $marginTop = 20;

        // Rectangle with rounded corners (resized)
        $buttonWidth = 170;
        $buttonHeight = 50;
        $rect = new Rectangle($this->uid . '_rect');
        $rect->setSize($buttonWidth, $buttonHeight);
        $rect->setOrigin($marginLeft, $marginTop);
        $rect->setColor(0xD3D3D3);
        $rect->setBorderRadius($this->borderRadius);
        $rect->setBorderColor(0x0000FF);
        $rect->setThickness(2);
        $rect->setRenderable(true);

        // Text aligned left on button, centered vertically
        $textX = $marginLeft + 10;
        $textY = $marginTop + 17;
        $text = new Text($this->uid . '_text');
        $text->setCenterAnchor(false);
        $text->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $text->setFontSize(16);
        $text->setOrigin($textX, $textY);
        $text->setText('Assembler');
        $text->setColor(0x0000FF);
        $text->setRenderable(true);

        // White square with blue border inside button, centered vertically with margins from top/right/bottom
        $squareSize = 36;
        $squareX = $marginLeft + $buttonWidth - $squareSize - 6;
        $squareY = $marginTop + ($buttonHeight / 2) - ($squareSize / 2);
        $square = new Rectangle($this->uid . '_square');
        $square->setSize($squareSize, $squareSize);
        $square->setOrigin($squareX, $squareY);
        $square->setColor(0xFFFFFF);
        $square->setBorderRadius(2);
        $square->setBorderColor(0x0000FF);
        $square->setThickness(2);
        $square->setRenderable(true);

        $this->drawItems[] = $rect;
        $this->drawItems[] = $text;
        $this->drawItems[] = $square;
    }
}
