<?php

namespace App\Custom;

class Square extends BasicDraw
{

    private $x;
    private $y;
    private $size;

    public function __construct()
    {
        parent::__construct('square');
    }

    public function setOrigin($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

   public function setSize($size) {
       $this->size = $size;
   }

    public function buildJson() {
        return $this->commonJson([
            'x' => $this->x,
            'y' => $this->y,
            'size' => $this->size
        ]);
    }

}