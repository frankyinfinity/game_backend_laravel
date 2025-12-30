<?php

namespace App\Custom\Manipulation;

use Illuminate\Support\Facades\Cache;

class ObjectCache
{
    private static array $buffers = [];

    /**
     * Start buffering for a session. Loads current cache into memory.
     */
    public static function buffer(string $sessionId): void
    {
        if (!isset(self::$buffers[$sessionId])) {
            self::$buffers[$sessionId] = Cache::get("objects:{$sessionId}", []);
        }
    }

    /**
     * Persist the buffer to the cache store and clear memory.
     */
    public static function flush(string $sessionId): void
    {
        if (isset(self::$buffers[$sessionId])) {
            Cache::put("objects:{$sessionId}", self::$buffers[$sessionId]);
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
        return Cache::get("objects:{$sessionId}", []);
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
        $buffered = isset(self::$buffers[$sessionId]);
        $data = $buffered ? self::$buffers[$sessionId] : Cache::get("objects:{$sessionId}", []);

        $data[$object['uid']] = $object;

        if ($buffered) {
            self::$buffers[$sessionId] = $data;
        } else {
            Cache::put("objects:{$sessionId}", $data);
        }
    }

    /**
     * Update specific attributes of an object.
     */
    public static function update(string $sessionId, string $uid, array $attributes): void
    {
        $buffered = isset(self::$buffers[$sessionId]);
        $data = $buffered ? self::$buffers[$sessionId] : Cache::get("objects:{$sessionId}", []);

        if (array_key_exists($uid, $data)) {
            foreach ($attributes as $key => $value) {
                $data[$uid][$key] = $value;
            }
        }

        if ($buffered) {
            self::$buffers[$sessionId] = $data;
        } else {
            Cache::put("objects:{$sessionId}", $data);
        }
    }

    /**
     * Remove an object.
     */
    public static function forget(string $sessionId, string $uid): void
    {
        $buffered = isset(self::$buffers[$sessionId]);
        $data = $buffered ? self::$buffers[$sessionId] : Cache::get("objects:{$sessionId}", []);

        unset($data[$uid]);

        if ($buffered) {
            self::$buffers[$sessionId] = $data;
        } else {
            Cache::put("objects:{$sessionId}", $data);
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
        Cache::forget("objects:{$sessionId}");
    }
}
