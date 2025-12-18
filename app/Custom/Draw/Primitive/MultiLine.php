<?php

namespace App\Custom\Draw\Primitive;

class MultiLine extends BasicDraw
{

    private array $points = [];

    public function __construct($uid = null)
    {
        if($uid == null) {
            $uid = uniqid();
        }
        parent::__construct('multi_line', $uid);
    }

    public function setPoint($x, $y): void
    {
        $this->points[] = ['x' => $x, 'y' => $y];
    }

    public function buildJson(): array
    {
        return $this->commonJson([
            'points' => $this->points
        ]);
    }

}
