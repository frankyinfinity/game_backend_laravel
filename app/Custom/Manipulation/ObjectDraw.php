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
        // Check if object is already an array or has buildJson() method
        if (is_array($this->object)) {
            // Already converted to array
            ObjectCache::put($this->sessionId, $this->object);
        } else {
            // Convert object to array using buildJson()
            $objectArray = $this->object->buildJson();
            ObjectCache::put($this->sessionId, $objectArray);
        }
    }


    public function get(): array
    {
        $this->write();
        // Return the object as-is (array or object) for the frontend
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_DRAW,
            'object' => is_array($this->object) ? $this->object : $this->object->buildJson(),
        ];
    }

}
