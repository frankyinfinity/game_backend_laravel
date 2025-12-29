<?php

namespace App\Custom\Manipulation;

use App\Helper\Helper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ObjectUpdate
{

    private string $uid;
    private int $sleep;
    private string $sessionId;
    private array $attributes = [];

    public function __construct(string $uid, string $sessionId, int $sleep = 0)
    {
        $this->uid = $uid;
        $this->sleep = $sleep;
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

        $uid = $this->uid;
        $attributes = $this->attributes;

        $items = [];
        $items[] = [
            'type' => Helper::DRAW_REQUEST_TYPE_UPDATE,
            'uid' => $uid,
            'attributes' => $attributes,
            'sleep' => $this->sleep
        ];

        if(array_key_exists('x', $attributes) || array_key_exists('y', $attributes)) {
            
            $x = $attributes['x'];
            $y = $attributes['y'];
         
            $key = "objects:{$this->sessionId}";
            $data = Cache::get($key, []);
            if(array_key_exists($uid, $data)) {
                $dataDraw = $data[$uid];
                $drawChildren = $dataDraw['children'];
                if(sizeof($drawChildren) > 0) {
                    foreach($drawChildren as $uidChild) {
                        if(array_key_exists($uidChild, $data)) {

                            $dataChild = $data[$uidChild];
                            $relativeX = $dataChild['relative_x'];
                            $relativeY = $dataChild['relative_y'];

                            $newX = $x + $relativeX;
                            $newY = $y + $relativeY;
                            
                            $items[] = [
                                'type' => Helper::DRAW_REQUEST_TYPE_UPDATE,
                                'uid' => $uidChild,
                                'attributes' => [
                                    'x' => $newX,
                                    'y' => $newY
                                ],
                                'sleep' => $this->sleep
                            ];

                        }
                    }
                }
            }
        }

        return $items;

    }

}
