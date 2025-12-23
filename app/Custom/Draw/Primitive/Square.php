<?php

namespace App\Custom\Draw\Primitive;

class Square extends BasicDraw
{

    private $size;

    public function __construct($uid = null)
    {
        if($uid == null) {
            $uid = uniqid();
        }
        parent::__construct('square', $uid);
    }

    public function setSize($size): void
    {
       $this->size = $size;
    }

    public function buildJson(): array
    {
        return $this->commonJson([
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
