<?php

namespace App\Custom;

class Circle extends BasicDraw
{

    private $x;
    private $y;
    private $radius;

    public function __construct($uid = null)
    {
        if($uid == null) {
            $uid = uniqid();
        }
        parent::__construct('circle', $uid);
    }

    public function setOrigin($x, $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }

   public function setRadius($radius): void
   {
       $this->radius = $radius;
   }

    public function buildJson(): array
    {
        return $this->commonJson([
            'x' => $this->x,
            'y' => $this->y,
            'radius' => $this->radius
        ]);
    }

}
