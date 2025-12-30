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
        ObjectCache::update($this->sessionId, $this->uid, $this->attributes);
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
         
            $dataDraw = ObjectCache::find($this->sessionId, $uid);

            if($dataDraw !== null) {
                $drawChildren = $dataDraw['children'] ?? [];
                if(is_array($drawChildren) && count($drawChildren) > 0) {
                    foreach($drawChildren as $uidChild) {
                        
                        $dataChild = ObjectCache::find($this->sessionId, $uidChild);
                        
                        if($dataChild !== null) {

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
