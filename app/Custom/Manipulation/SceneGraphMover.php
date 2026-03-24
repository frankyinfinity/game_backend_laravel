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

        $this->appendChildrenMoveUpdates($parentUid, $dx, $dy, $sleep, true, $items, $moved);

        return $items;
    }

    public function syncChildrenPositions(string $parentUid, float $dx, float $dy): void
    {
        $items = [];
        $moved = [];

        $this->appendChildrenMoveUpdates($parentUid, $dx, $dy, 0, false, $items, $moved);
    }

    private function appendChildrenMoveUpdates(
        string $parentUid,
        float $dx,
        float $dy,
        int $sleep,
        bool $emitUpdates,
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

            if (isset($child['points']) && is_array($child['points']) && !empty($child['points'])) {
                $movedPoints = [];
                foreach ($child['points'] as $point) {
                    if (!is_array($point)) {
                        continue;
                    }
                    $movedPoints[] = [
                        'x' => (isset($point['x']) && is_numeric($point['x'])) ? ((float) $point['x']) + $dx : $point['x'],
                        'y' => (isset($point['y']) && is_numeric($point['y'])) ? ((float) $point['y']) + $dy : $point['y'],
                    ];
                }

                if (!empty($movedPoints)) {
                    $newAttributes = ['points' => $movedPoints];
                }
            } else {
                $newAttributes = [];
                if (isset($child['x']) && is_numeric($child['x'])) {
                    $newAttributes['x'] = ((float) $child['x']) + $dx;
                }
                if (isset($child['y']) && is_numeric($child['y'])) {
                    $newAttributes['y'] = ((float) $child['y']) + $dy;
                }
            }

            if (!empty($newAttributes)) {
                $this->store->update($childUid, $newAttributes);

                if ($emitUpdates) {
                    $items[] = (new UpdatePayload($childUid, $newAttributes, $sleep))->toArray();
                }

                $moved[$childUid] = true;
            }

            $this->appendChildrenMoveUpdates($childUid, $dx, $dy, $sleep, $emitUpdates, $items, $moved);
        }
    }
}
