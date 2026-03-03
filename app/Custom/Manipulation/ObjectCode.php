<?php

namespace App\Custom\Manipulation;

use App\Helper\Helper;

class ObjectCode
{
    private string $code;
    private int $sleep;

    public function __construct(string $code, int $sleep = 0)
    {
        $this->code = $code;
        $this->sleep = $sleep;
    }

    public function get(): array
    {
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_CODE,
            'code' => $this->code,
            'sleep' => $this->sleep,
        ];
    }
}
