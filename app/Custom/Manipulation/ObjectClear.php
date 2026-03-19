<?php

namespace App\Custom\Manipulation;

use App\Custom\Manipulation\Contracts\ManipulationCommand;
use App\Custom\Manipulation\Payload\ClearPayload;

class ObjectClear implements ManipulationCommand
{
    private string $uid;
    private SessionObjectStore $store;
    private string $sessionId;

    public function __construct(string $uid, string $sessionId)
    {
        $this->uid = $uid;
        $this->sessionId = $sessionId;
        $this->store = new SessionObjectStore($sessionId);
    }

    private function write(): void
    {
        $this->store->forget($this->uid);
    }

    public function apply(): array
    {
        $this->write();

        return (new ClearPayload($this->uid))->toArray();
    }

    public function get(): array
    {
        return $this->apply();
    }
}
