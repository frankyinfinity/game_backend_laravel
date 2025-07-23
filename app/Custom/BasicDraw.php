<?php

namespace App\Custom;

class BasicDraw
{
    private $type;
    private $color;
    private $thickness;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function setThickness($thickness) {
        $this->thickness = $thickness;
    }

    public function commonJson($extra) 
    {
        return [
            'type' => $this->type,
            'color' => $this->color,
            'thickness' => $this->thickness
        ]+$extra;
    }

}