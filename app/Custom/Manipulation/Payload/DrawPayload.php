<?php

namespace App\Custom\Manipulation\Payload;

use App\Helper\Helper;

class DrawPayload
{
    public function __construct(
        private readonly array $object
    ) {
    }

    public function toArray(): array
    {
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_DRAW,
            'object' => $this->object,
        ];
    }
}
