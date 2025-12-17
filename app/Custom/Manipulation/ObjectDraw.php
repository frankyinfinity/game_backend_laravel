<?php

namespace App\Custom\Manipulation;

use App\Helper\Helper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Valuestore\Valuestore;
use Illuminate\Support\Facades\File;

class ObjectDraw
{

    private $object;
    private string $sessionId;
    public function __construct($object, $sessionId)
    {
        $this->object = $object;
        $this->sessionId = $sessionId;
    }

    private function write(): void
    {

        $object = $this->object;

        $key = "objects:{$this->sessionId}";
        $data = Cache::get($key, []);

        $data[$object['uid']] = $object;
        Cache::put($key, $data);

    }


    public function get(): array
    {
        $this->write();
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_DRAW,
            'object' => $this->object,
        ];
    }

}
