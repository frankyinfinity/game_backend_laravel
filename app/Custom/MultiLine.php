<?php

namespace App\Custom;

class MultiLine extends BasicDraw
{

    private $points = [];

    public function __construct($uid = null)
    {
        if($uid == null) {
            $uid = uniqid();
        }
        parent::__construct('multi_line', $uid);
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