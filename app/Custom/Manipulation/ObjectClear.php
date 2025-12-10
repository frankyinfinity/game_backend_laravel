<?php

namespace App\Custom\Manipulation;

use App\Helper\Helper;

class ObjectClear
{

    private $uid;
    private string $sessionId;
    public function __construct($uid, $sessionId)
    {
        $this->uid = $uid;
        $this->sessionId = $sessionId;
    }

    public function get(): array
    {
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_CLEAR,
            'uid' => $this->uid,
        ];
    }

}
