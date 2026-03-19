<?php

namespace App\Custom\Manipulation\Payload;

use App\Helper\Helper;

class CodePayload
{
    public function __construct(
        private readonly string $code,
        private readonly int $sleep = 0
    ) {
    }

    public function toArray(): array
    {
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_CODE,
            'code' => $this->code,
            'sleep' => $this->sleep,
        ];
    }
}
