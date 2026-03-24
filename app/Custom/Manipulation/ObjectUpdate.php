<?php

namespace App\Custom\Manipulation;

use App\Custom\Manipulation\Contracts\ManipulationCommand;
use App\Custom\Manipulation\Payload\UpdatePayload;

class ObjectUpdate implements ManipulationCommand
{
    private string $uid;
    private int $sleep;
    private string $sessionId;
    private array $attributes = [];
    private SessionObjectStore $store;
    private SceneGraphMover $sceneGraphMover;

    public function __construct(string $uid, string $sessionId, int $sleep = 0)
    {
        $this->uid = $uid;
        $this->sleep = $sleep;
        $this->sessionId = $sessionId;
        $this->store = new SessionObjectStore($sessionId);
        $this->sceneGraphMover = new SceneGraphMover($this->store);
    }

    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function setAttributesArray(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (is_string($key) && $key !== '') {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    public function setAttributes(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    private function write(): void
    {
        $this->store->update($this->uid, $this->attributes);
    }

    public function apply(): array
    {
        $uid = $this->uid;
        $attributes = $this->attributes;
        $parentBefore = $this->store->find($uid);

        $this->write();

        $items = [];
        $items[] = (new UpdatePayload($uid, $attributes, $this->sleep))->toArray();

        if (array_key_exists('x', $attributes) || array_key_exists('y', $attributes)) {
            $xBefore = is_array($parentBefore) ? ($parentBefore['x'] ?? null) : null;
            $yBefore = is_array($parentBefore) ? ($parentBefore['y'] ?? null) : null;

            $xAfter = array_key_exists('x', $attributes) ? $attributes['x'] : $xBefore;
            $yAfter = array_key_exists('y', $attributes) ? $attributes['y'] : $yBefore;

            if ($xAfter !== null && $yAfter !== null) {
                $dx = ((float) $xAfter) - ((float) ($xBefore ?? $xAfter));
                $dy = ((float) $yAfter) - ((float) ($yBefore ?? $yAfter));

                if ($dx !== 0.0 || $dy !== 0.0) {
                    $items = array_merge(
                        $items,
                        // Children should follow immediately, even if the root movement is delayed.
                        $this->sceneGraphMover->moveChildren($uid, $dx, $dy, 0)
                    );
                }
            }
        }

        return $items;
    }

    public function get(): array
    {
        return $this->apply();
    }
}
