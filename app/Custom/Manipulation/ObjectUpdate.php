<?php

namespace App\Custom\Manipulation;

use App\Helper\Helper;

class ObjectUpdate
{

    private string $uid;
    private array $attributes = [];

    public function __construct(string $uid)
    {
        $this->uid = $uid;
    }

    public function setAttributes(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function get(): array
    {
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_UPDATE,
            'uid' => $this->uid,
            'attributes' => $this->attributes
        ];
    }

}
