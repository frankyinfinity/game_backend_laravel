<?php

namespace App\Custom\Manipulation;

use App\Custom\Manipulation\Payload\UpdatePayload;

class SceneGraphMover
{
    public function __construct(
        private readonly SessionObjectStore $store
    ) {
    }

    public function moveChildren(string $parentUid, float $dx, float $dy, int $sleep = 0): array
    {
        $items = [];
        $moved = [];

        $this->appendChildrenMoveUpdates($parentUid, $dx, $dy, $sleep, $items, $moved);

        return $items;
    }

    private function appendChildrenMoveUpdates(
        string $parentUid,
        float $dx,
        float $dy,
        int $sleep,
        array &$items,
        array &$moved
    ): void {
        $parent = $this->store->find($parentUid);
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

            $child = $this->store->find($childUid);
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
                $this->store->update($childUid, $newAttributes);
                $items[] = (new UpdatePayload($childUid, $newAttributes, $sleep))->toArray();
                $moved[$childUid] = true;
            }

            $this->appendChildrenMoveUpdates($childUid, $dx, $dy, $sleep, $items, $moved);
        }
    }
}
