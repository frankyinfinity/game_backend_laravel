<?php

namespace App\Custom\Draw\Primitive;

class Rectangle extends BasicDraw
{

    private $width;
    private $height;
    private $borderRadius;
    private $borderColor;

    public function __construct($uid = null)
    {
        if($uid == null) {
            $uid = uniqid();
        }
        parent::__construct('rectangle', $uid);
    }

   public function setSize($width, $height): void
   {
       $this->width = $width;
       $this->height = $height;
   }

   public function setBorderRadius($radius): void
   {
       $this->borderRadius = $radius;
   }

   public function setBorderColor($color): void
   {
       $this->borderColor = $color;
   }

    public function buildJson(): array
    {
        return $this->commonJson([
            'width' => $this->width,
            'height' => $this->height,
            'borderRadius' => $this->borderRadius ?? 0,
            'borderColor' => $this->borderColor ?? null,
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
