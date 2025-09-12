<?php

namespace App\Custom;
use App\Custom\Text;

class ButtonDraw {

    private $uid;
    public function __construct($uid) {
        $this->uid = $uid;
    }

    private array $items = [];
    public function getItems(): array
    {
        return $this->items;
    }

    private $width;
    private $height;
    private $x;
    private $y;
    private $string;
    private $colorButton;
    private $colorString;

    public function setSize($width, $height): void
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function setOrigin($x, $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function setString($string): void
    {
        $this->string = $string;
    }

    public function setColorButton($color): void
    {
        $this->colorButton = $color;
    }

    public function setColorString($color): void
    {
        $this->colorString = $color;
    }

    public function build() {

        $uid = $this->uid;
        $width = $this->width;
        $height = $this->height;
        $x = $this->x;
        $y = $this->y;
        $string = $this->string;
        $colorButton = $this->colorButton;
        $colorString = $this->colorString;

        //Rect
        $rect = new \App\Custom\Rectangle($uid.'_rect');
        $rect->setSize($width, $height);
        $rect->setOrigin($x, $y);
        $rect->setColor($colorButton);
        $rect->setRenderable(false);

        //Text
        $text = new Text($uid.'_text');
        $text->setOrigin($x, $y);
        $text->setText($string);
        $text->setColor($colorString);
        $text->setRenderable(false);

        $this->items[] = $rect;
        $this->items[] = $text;

    }

}
