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
        $uid = $this->uid;
        $attributes = $this->attributes;
        $parentBefore = ObjectCache::find($this->sessionId, $uid);

        $this->write();

        $items = [];
        $items[] = [
            'type' => Helper::DRAW_REQUEST_TYPE_UPDATE,
            'uid' => $uid,
            'attributes' => $attributes,
            'sleep' => $this->sleep
        ];

        if(array_key_exists('x', $attributes) || array_key_exists('y', $attributes)) {
            $xBefore = is_array($parentBefore) ? ($parentBefore['x'] ?? null) : null;
            $yBefore = is_array($parentBefore) ? ($parentBefore['y'] ?? null) : null;

            $xAfter = array_key_exists('x', $attributes) ? $attributes['x'] : $xBefore;
            $yAfter = array_key_exists('y', $attributes) ? $attributes['y'] : $yBefore;

            if ($xAfter === null || $yAfter === null) {
                return $items;
            }

            $dx = ((float) $xAfter) - ((float) ($xBefore ?? $xAfter));
            $dy = ((float) $yAfter) - ((float) ($yBefore ?? $yAfter));
            if ($dx === 0.0 && $dy === 0.0) {
                return $items;
            }

            $moved = [];
            $this->appendChildrenMoveUpdates($uid, $dx, $dy, $items, $moved);
        }

        return $items;

    }

    private function appendChildrenMoveUpdates(string $parentUid, float $dx, float $dy, array &$items, array &$moved): void
    {
        $parent = ObjectCache::find($this->sessionId, $parentUid);
        if (!is_array($parent)) {
            return;
        }

        $children = $parent['children'] ?? [];
        if (!is_array($children) || empty($children)) {
            return;
        }

        foreach ($children as $childUid) {
            if (!is_string($childUid) || $childUid === '' || isset($moved[$childUid])) {
                continue;
            }

            $child = ObjectCache::find($this->sessionId, $childUid);
            if (!is_array($child)) {
                continue;
            }

            $newAttributes = [];
            if (isset($child['x']) && is_numeric($child['x'])) {
                $newAttributes['x'] = ((float) $child['x']) + $dx;
            }
            if (isset($child['y']) && is_numeric($child['y'])) {
                $newAttributes['y'] = ((float) $child['y']) + $dy;
            }

            if (!empty($newAttributes)) {
                $items[] = [
                    'type' => Helper::DRAW_REQUEST_TYPE_UPDATE,
                    'uid' => $childUid,
                    'attributes' => $newAttributes,
                    'sleep' => $this->sleep
                ];
                ObjectCache::update($this->sessionId, $childUid, $newAttributes);
                $moved[$childUid] = true;
            }

            $this->appendChildrenMoveUpdates($childUid, $dx, $dy, $items, $moved);
        }
    }

}
