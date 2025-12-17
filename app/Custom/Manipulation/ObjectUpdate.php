<?php

namespace App\Custom\Manipulation;

use App\Helper\Helper;
use Illuminate\Support\Facades\Cache;

class ObjectUpdate
{

    private string $uid;
    private string $sessionId;
    private array $attributes = [];

    public function __construct(string $uid, string $sessionId)
    {
        $this->uid = $uid;
        $this->sessionId = $sessionId;
    }

    public function setAttributes(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    private function write(): void
    {

        $key = "objects:{$this->sessionId}";

        $uid = $this->uid;
        $attributes = $this->attributes;

        $data = Cache::get($key, []);
        foreach ($attributes as $key => $value) {
            $data[$uid][$key] = $value;
        }

        Cache::put($key, $data);

    }

    public function get(): array
    {
        $this->write();
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_UPDATE,
            'uid' => $this->uid,
            'attributes' => $this->attributes
        ];
    }

}
