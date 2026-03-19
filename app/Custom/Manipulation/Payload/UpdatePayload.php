<?php

namespace App\Custom\Manipulation\Payload;

use App\Helper\Helper;

class UpdatePayload
{
    public function __construct(
        private readonly string $uid,
        private readonly array $attributes,
        private readonly int $sleep = 0
    ) {
    }

    public function toArray(): array
    {
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_UPDATE,
            'uid' => $this->uid,
            'attributes' => $this->attributes,
            'sleep' => $this->sleep,
        ];
    }
}
