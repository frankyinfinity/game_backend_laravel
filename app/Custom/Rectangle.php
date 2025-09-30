<?php

namespace App\Custom;

class Rectangle extends BasicDraw
{

    private $x;
    private $y;
    private $width;
    private $height;

    public function __construct($uid = null)
    {
        if($uid == null) {
            $uid = uniqid();
        }
        parent::__construct('rectangle', $uid);
    }

    public function setOrigin($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

   public function setSize($width, $height) {
       $this->width = $width;
       $this->height = $height;
   }

    public function buildJson() {
        return $this->commonJson([
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height
        ]);
    }

    private function getCenterX(): float
    {
        return $this->x + ($this->width / 2);
    }

    private function getCenterY(): float
    {
        return $this->y + ($this->height / 2);
    }

    public function getCenter(): array
    {
        return [
            'x' => $this->getCenterX(),
            'y' => $this->getCenterY(),
        ];
    }

}
