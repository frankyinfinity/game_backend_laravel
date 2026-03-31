<?php

namespace App\Custom\Manipulation;

use App\Models\Player;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class ObjectCache
{
    private static array $buffers = [];
    private static array $dirty = [];
    private static array $resolvedPlayers = [];

    /**
     * Start buffering for a session. Loads current state into memory.
     */
    public static function buffer(string $sessionId): void
    {
        if (!isset(self::$buffers[$sessionId])) {
            self::$buffers[$sessionId] = self::read($sessionId);
        }

        self::$dirty[$sessionId] = false;
    }

    /**
     * Queue the buffer persistence to disk and clear memory.
     */
    public static function flush(string $sessionId): void
    {
        if (isset(self::$buffers[$sessionId])) {
            if (self::$dirty[$sessionId] ?? false) {
                $player = self::resolvePlayer($sessionId);
                self::writeToDisk($sessionId, self::$buffers[$sessionId], $player);
            }

            unset(self::$buffers[$sessionId]);
            unset(self::$dirty[$sessionId]);
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

        self::store($sessionId, $data, $buffered);
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

        self::store($sessionId, $data, $buffered);
    }

    /**
     * Remove an object.
     */
    public static function forget(string $sessionId, string $uid): void
    {
        $buffered = isset(self::$buffers[$sessionId]);
        $data = $buffered ? self::$buffers[$sessionId] : self::read($sessionId);

        unset($data[$uid]);

        self::store($sessionId, $data, $buffered);
    }

    /**
     * Clear the entire cache for a session.
     */
    public static function clear(string $sessionId): void
    {
        if (isset(self::$buffers[$sessionId])) {
            unset(self::$buffers[$sessionId]);
        }
        unset(self::$dirty[$sessionId]);

        $player = self::resolvePlayer($sessionId);
        self::deleteFromDisk($sessionId, $player);
    }

    private static function read(string $sessionId): array
    {
        if (isset(self::$buffers[$sessionId])) {
            return self::$buffers[$sessionId];
        }

        $player = self::resolvePlayer($sessionId);
        return self::readFromDisk($sessionId, $player);
    }

    private static function resolvePlayer(string $sessionId): ?Player
    {
        if ($sessionId === '') {
            return null;
        }

        if (array_key_exists($sessionId, self::$resolvedPlayers)) {
            return self::$resolvedPlayers[$sessionId];
        }

        self::$resolvedPlayers[$sessionId] = Player::query()
            ->where('actual_session_id', $sessionId)
            ->first();

        if (self::$resolvedPlayers[$sessionId] === null) {
            \Log::warning('ObjectCache sessione non associata a player', [
                'session_id' => $sessionId,
            ]);
        }

        return self::$resolvedPlayers[$sessionId];
    }

    private static function store(string $sessionId, array $data, bool $buffered): void
    {
        self::$buffers[$sessionId] = $data;
        self::$dirty[$sessionId] = true;

        if ($buffered) {
            return;
        }

        $player = self::resolvePlayer($sessionId);
        self::writeToDisk($sessionId, $data, $player);
        self::$dirty[$sessionId] = false;
    }

    public static function volumeCachePath(string $sessionId): string
    {
        return 'object-cache/' . self::sessionFileName($sessionId);
    }

    public static function sessionVolumePath(string $sessionId): string
    {
        return self::volumeCachePath($sessionId);
    }

    public static function legacyVolumeCachePath(string $sessionId): string
    {
        return 'object-cache/' . sha1($sessionId) . '.json';
    }

    private static function sessionFileName(string $sessionId): string
    {
        $sessionId = trim($sessionId);
        if ($sessionId === '') {
            throw new InvalidArgumentException('Il session_id non puo essere vuoto.');
        }

        $player = self::resolvePlayer($sessionId);
        if ($player !== null) {
            return self::playerFileName($player->id);
        }

        $safeSessionId = preg_replace('/[^A-Za-z0-9._-]/', '_', $sessionId);
        return 'object_cache_session_' . $safeSessionId . '.json';
    }

    private static function diskFileName(string $sessionId, ?Player $player = null): string
    {
        $sessionId = trim($sessionId);
        if ($sessionId === '') {
            throw new InvalidArgumentException('Il session_id non puo essere vuoto.');
        }

        if ($player !== null) {
            return self::playerFileName($player->id);
        }

        $safeSessionId = preg_replace('/[^A-Za-z0-9._-]/', '_', $sessionId);
        return 'object_cache_session_' . $safeSessionId . '.json';
    }

    private static function playerFileName(int $playerId): string
    {
        return 'object_cache_player_' . $playerId . '.json';
    }

    private static function readFromDisk(string $sessionId, ?Player $player = null): array
    {
        $disk = Storage::disk('object_cache');
        $path = self::diskFileName($sessionId, $player);

        if (!$disk->exists($path)) {
            return [];
        }

        $json = $disk->get($path);
        if ($json === null || trim($json) === '') {
            return [];
        }

        return self::decodeJson($json);
    }

    private static function writeToDisk(string $sessionId, array $data, ?Player $player = null): void
    {
        try {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('Impossibile serializzare ObjectCache per il disk locale: ' . $e->getMessage(), 0, $e);
        }

        Storage::disk('object_cache')->put(self::diskFileName($sessionId, $player), $json);
    }

    private static function deleteFromDisk(string $sessionId, ?Player $player = null): void
    {
        Storage::disk('object_cache')->delete(self::diskFileName($sessionId, $player));
    }

    private static function decodeJson(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('Impossibile leggere ObjectCache dal disk: ' . $e->getMessage(), 0, $e);
        }

        return is_array($decoded) ? $decoded : [];
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
