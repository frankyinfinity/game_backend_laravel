<?php

namespace App\Custom;

class Rectangle extends BasicDraw
{

    private $x;
    private $y;
    private $width;
    private $height;

    public function __construct()
    {
        parent::__construct('rectangle');
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

}