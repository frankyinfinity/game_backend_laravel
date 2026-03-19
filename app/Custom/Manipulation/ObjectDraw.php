<?php

namespace App\Custom\Manipulation;

use App\Custom\Manipulation\Contracts\ManipulationCommand;
use App\Custom\Manipulation\Payload\DrawPayload;

class ObjectDraw implements ManipulationCommand
{
    private array $object;
    private SessionObjectStore $store;
    private string $sessionId;

    public function __construct(array|object $object, string $sessionId)
    {
        $this->object = ObjectNormalizer::normalize($object);
        $this->sessionId = $sessionId;
        $this->store = new SessionObjectStore($sessionId);
    }

    private function write(): void
    {
        $this->store->put($this->object);
    }

    public function apply(): array
    {
        $this->write();

        return (new DrawPayload($this->object))->toArray();
    }

    public function get(): array
    {
        return $this->apply();
    }
}
