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
        if(sizeof($this->points) === 0) {
            $this->x = $x;
            $this->y = $y;
        }
        $this->points[] = ['x' => $x, 'y' => $y];
    }

    public function translatePoints(int $deltaX, int $deltaY): void
    {
        if ($deltaX === 0 && $deltaY === 0) {
            return;
        }

        foreach ($this->points as $index => $point) {
            $this->points[$index]['x'] = $point['x'] + $deltaX;
            $this->points[$index]['y'] = $point['y'] + $deltaY;
        }
    }

    public function buildJson(): array
    {
        return $this->commonJson([
            'points' => $this->points
        ]);
    }

}
