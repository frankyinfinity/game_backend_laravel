<?php

namespace App\Custom\Manipulation;

use App\Helper\Helper;
use Illuminate\Support\Facades\Cache;

class ObjectClear
{

    private $uid;
    private string $sessionId;
    public function __construct($uid, $sessionId)
    {
        $this->uid = $uid;
        $this->sessionId = $sessionId;
    }

    private function write(): void
    {

        $object = $this->object;

        $key = "objects:{$this->sessionId}";
        $data = Cache::get($key, []);

        unset($data[$object['uid']]);
        Cache::put($key, $data);

    }

    public function get(): array
    {
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_CLEAR,
            'uid' => $this->uid,
        ];
    }

}
