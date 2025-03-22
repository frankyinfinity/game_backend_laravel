<?php

namespace App\Custom;

class Circle extends BasicDraw
{

    private $x;
    private $y;
    private $radius;

    public function __construct()
    {
        parent::__construct('radius');
    }

    public function setOrigin($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

   public function setRadius($radius) {
       $this->radius = $radius;
   }

    public function buildJson() {
        return $this->commonJson([
            'x' => $this->x,
            'y' => $this->y,
            'radius' => $this->radius
        ]);
    }

}