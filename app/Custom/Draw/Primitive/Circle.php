<?php

namespace App\Custom\Draw\Primitive;

class Circle extends BasicDraw
{

    private $radius;

    public function __construct($uid = null)
    {
        if($uid == null) {
            $uid = uniqid();
        }
        parent::__construct('circle', $uid);
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
