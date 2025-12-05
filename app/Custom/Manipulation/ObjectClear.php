<?php

namespace App\Custom\Manipulation;

use App\Helper\Helper;

class ObjectClear
{

    private $uid;
    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    public function get(): array
    {
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_CLEAR,
            'uid' => $this->uid,
        ];
    }

}
