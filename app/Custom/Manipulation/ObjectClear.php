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
        ObjectCache::forget($this->sessionId, $this->uid);
    }

    public function get(): array
    {
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_CLEAR,
            'uid' => $this->uid,
        ];
    }

}
