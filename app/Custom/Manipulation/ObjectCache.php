<?php

namespace App\Custom\Manipulation;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class ObjectCache
{
    private static array $buffers = [];

    /**
     * Start buffering for a session. Loads current cache into memory.
     */
    public static function buffer(string $sessionId): void
    {
        if (!isset(self::$buffers[$sessionId])) {
            self::$buffers[$sessionId] = self::read($sessionId);
        }
    }

    /**
     * Persist the buffer to the cache store and clear memory.
     */
    public static function flush(string $sessionId): void
    {
        if (isset(self::$buffers[$sessionId])) {
            Cache::put(self::key($sessionId), self::$buffers[$sessionId]);
            unset(self::$buffers[$sessionId]);
        }
    }

    /**
     * Get the full objects array for a session (from buffer or cache).
     */
    public static function all(string $sessionId): array
    {
        if (isset(self::$buffers[$sessionId])) {
            return self::$buffers[$sessionId];
        }

        return self::read($sessionId);
    }

    /**
     * Get a specific object by UID.
     */
    public static function find(string $sessionId, string $uid): ?array
    {
        $data = self::all($sessionId);
        return $data[$uid] ?? null;
    }

    /**
     * Add or overwrite an object.
     */
    public static function put(string $sessionId, array $object): void
    {
        $uid = self::extractUid($object);
        $buffered = isset(self::$buffers[$sessionId]);
        $data = $buffered ? self::$buffers[$sessionId] : self::read($sessionId);

        $data[$uid] = $object;

        if ($buffered) {
            self::$buffers[$sessionId] = $data;
        } else {
            Cache::put(self::key($sessionId), $data);
        }
    }

    /**
     * Update specific attributes of an object.
     */
    public static function update(string $sessionId, string $uid, array $attributes): void
    {
        $buffered = isset(self::$buffers[$sessionId]);
        $data = $buffered ? self::$buffers[$sessionId] : self::read($sessionId);

        if (array_key_exists($uid, $data)) {
            foreach ($attributes as $key => $value) {
                $data[$uid][$key] = $value;
            }
        }

        if ($buffered) {
            self::$buffers[$sessionId] = $data;
        } else {
            Cache::put(self::key($sessionId), $data);
        }
    }

    /**
     * Remove an object.
     */
    public static function forget(string $sessionId, string $uid): void
    {
        $buffered = isset(self::$buffers[$sessionId]);
        $data = $buffered ? self::$buffers[$sessionId] : self::read($sessionId);

        unset($data[$uid]);

        if ($buffered) {
            self::$buffers[$sessionId] = $data;
        } else {
            Cache::put(self::key($sessionId), $data);
        }
    }

    /**
     * Clear the entire cache for a session.
     */
    public static function clear(string $sessionId): void
    {
        if (isset(self::$buffers[$sessionId])) {
            unset(self::$buffers[$sessionId]);
        }
        Cache::forget(self::key($sessionId));
    }

    private static function key(string $sessionId): string
    {
        return "objects:{$sessionId}";
    }

    private static function read(string $sessionId): array
    {
        $data = Cache::get(self::key($sessionId), []);

        return is_array($data) ? $data : [];
    }

    private static function extractUid(array $object): string
    {
        $uid = $object['uid'] ?? null;
        if (!is_string($uid) || $uid === '') {
            throw new InvalidArgumentException('Object cache entries require a non-empty uid.');
        }

        return $uid;
    }
}
