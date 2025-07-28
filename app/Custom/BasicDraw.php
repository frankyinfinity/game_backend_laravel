<?php

namespace App\Custom;

class BasicDraw
{
    private $uid;
    private $type;
    private $color;
    private $thickness;

    public function __construct($type, $uid)
    {
        $this->type = $type;
        $this->uid = $uid;
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