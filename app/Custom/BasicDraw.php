<?php

namespace App\Custom;

class BasicDraw
{
    private $type;
    private $color;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function commonJson($extra) 
    {
        return json_encode([
            'type' => $this->type,
            'color' => $this->color
        ]+$extra);
    }

}