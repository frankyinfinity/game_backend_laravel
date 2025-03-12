<?php

namespace App\Custom;

class MultiLine extends BasicDraw
{

    private $points = [];

    public function __construct()
    {
        parent::__construct('multi_line');
    }

    public function setPoint($x, $y)
    {
        $this->points[] = ['x' => $x, 'y' => $y];
    }

    public function buildJson() {
        return $this->commonJson([
            'points' => $this->points
        ]);
    }

}