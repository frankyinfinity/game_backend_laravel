<?php

namespace App\Custom;

class Line extends BasicDraw
{

    private $x1;
    private $y1;
    private $x2;
    private $y2;

    public function __construct()
    {
        parent::__construct('line');
    }

    public function setPoint($x1, $y1, $x2, $y2)
    {
        $this->x1 = $x1;
        $this->y1 = $y1;
        $this->x2 = $x2;
        $this->y2 = $y2;
    }

    public function buildJson() {
        return $this->commonJson([
            'x1' => $this->x1,
            'y1' => $this->y1,
            'x2' => $this->x2,
            'y2' => $this->y2
        ]);
    }

}