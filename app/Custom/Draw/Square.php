<?php

namespace App\Custom\Draw;

class Square extends BasicDraw
{

    private $x;
    private $y;
    private $size;

    public function __construct($uid = null)
    {
        if($uid == null) {
            $uid = uniqid();
        }
        parent::__construct('square', $uid);
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

    private function getCenterX(): float
    {
        return $this->x + ($this->size / 2);
    }

    private function getCenterY(): float
    {
        return $this->y + ($this->size / 2);
    }

    public function getCenter(): array
    {
        return [
            'x' => $this->getCenterX(),
            'y' => $this->getCenterY(),
        ];
    }

}
