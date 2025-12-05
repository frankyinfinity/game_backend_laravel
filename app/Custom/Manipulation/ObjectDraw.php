<?php

namespace App\Custom\Manipulation;

use App\Helper\Helper;

class ObjectDraw
{

    private $object;
    public function __construct($object)
    {
        $this->object = $object;
    }

    public function get(): array
    {
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_DRAW,
            'object' => $this->object,
        ];
    }

}
