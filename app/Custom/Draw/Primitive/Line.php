<?php

namespace App\Custom\Draw\Primitive;

class Line extends BasicDraw
{
    private array $points = [];

    public function __construct($uid = null)
    {
        if ($uid == null) {
            $uid = uniqid();
        }
        parent::__construct('line', $uid);
    }

    public function setPoint($x, $y): void
    {
        if (sizeof($this->points) === 0) {
            $this->x = $x;
            $this->y = $y;
        }

        $this->points[] = ['x' => $x, 'y' => $y];
    }

    public function buildJson(): array
    {
        return $this->commonJson([
            'points' => $this->points
        ]);
    }
}
