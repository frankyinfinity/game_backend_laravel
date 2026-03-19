<?php

namespace App\Custom\Manipulation;

class SessionObjectStore
{
    public function __construct(
        private readonly string $sessionId
    ) {
    }

    public function all(): array
    {
        return ObjectCache::all($this->sessionId);
    }

    public function find(string $uid): ?array
    {
        return ObjectCache::find($this->sessionId, $uid);
    }

    public function put(array $object): void
    {
        ObjectCache::put($this->sessionId, $object);
    }

    public function update(string $uid, array $attributes): void
    {
        ObjectCache::update($this->sessionId, $uid, $attributes);
    }

    public function forget(string $uid): void
    {
        ObjectCache::forget($this->sessionId, $uid);
    }
}
