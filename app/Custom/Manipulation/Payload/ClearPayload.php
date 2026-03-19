<?php

namespace App\Custom\Manipulation\Payload;

use App\Helper\Helper;

class ClearPayload
{
    public function __construct(
        private readonly string $uid
    ) {
    }

    public function toArray(): array
    {
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_CLEAR,
            'uid' => $this->uid,
        ];
    }
}
