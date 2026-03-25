<?php

namespace App\Custom\Manipulation;

use App\Models\Player;
use App\Services\DockerContainerService;
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
     * Persist the buffer to the player volume and clear memory.
     */
    public static function flush(string $sessionId): void
    {
        if (isset(self::$buffers[$sessionId])) {
            $player = self::resolvePlayer($sessionId);
            if ($player !== null && (self::$dirty[$sessionId] ?? false)) {
                \Log::info('ObjectCache flush verso volume player', [
                    'session_id' => $sessionId,
                    'player_id' => $player->id,
                    'entries' => count(self::$buffers[$sessionId]),
                ]);
                self::writeToPlayerStorage($player, $sessionId, self::$buffers[$sessionId]);
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
        if ($player !== null) {
            self::deletePlayerStorage($player, $sessionId);
        }
    }

    private static function read(string $sessionId): array
    {
        if (isset(self::$buffers[$sessionId])) {
            return self::$buffers[$sessionId];
        }

        $player = self::resolvePlayer($sessionId);
        if ($player !== null) {
            return self::readFromPlayerStorage($player, $sessionId);
        }

        return [];
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
        if ($player !== null) {
            self::writeToPlayerStorage($player, $sessionId, $data);
            self::$dirty[$sessionId] = false;
        }
    }

    private static function volumeCachePath(string $sessionId): string
    {
        return 'object-cache/' . self::sessionFileName($sessionId);
    }

    public static function sessionVolumePath(string $sessionId): string
    {
        return self::volumeCachePath($sessionId);
    }

    private static function legacyVolumeCachePath(string $sessionId): string
    {
        return 'object-cache/' . sha1($sessionId) . '.json';
    }

    private static function sessionFileName(string $sessionId): string
    {
        $sessionId = trim($sessionId);
        if ($sessionId === '') {
            throw new InvalidArgumentException('Il session_id non può essere vuoto.');
        }

        $safeSessionId = preg_replace('/[^A-Za-z0-9._-]/', '_', $sessionId);
        return $safeSessionId . '.json';
    }

    private static function readFromPlayerStorage(Player $player, string $sessionId): array
    {
        $service = app(DockerContainerService::class);
        $json = $service->readPlayerVolumeFile($player, self::volumeCachePath($sessionId));

        if ($json === null || trim($json) === '') {
            $legacyJson = $service->readPlayerVolumeFile($player, self::legacyVolumeCachePath($sessionId));
            if ($legacyJson !== null && trim($legacyJson) !== '') {
                return self::decodePlayerStorage($legacyJson);
            }

            return [];
        }

        return self::decodePlayerStorage($json);
    }

    private static function writeToPlayerStorage(Player $player, string $sessionId, array $data): void
    {
        try {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('Impossibile serializzare ObjectCache per il volume del player: ' . $e->getMessage(), 0, $e);
        }

        $service = app(DockerContainerService::class);
        $service->writePlayerVolumeFile($player, self::volumeCachePath($sessionId), $json);
        $service->deletePlayerVolumeFile($player, self::legacyVolumeCachePath($sessionId));

        \Log::info('ObjectCache salvato nel volume player', [
            'session_id' => $sessionId,
            'player_id' => $player->id,
            'path' => self::volumeCachePath($sessionId),
            'entries' => count($data),
        ]);
    }

    private static function deletePlayerStorage(Player $player, string $sessionId): void
    {
        $service = app(DockerContainerService::class);
        $service->deletePlayerVolumeFile($player, self::volumeCachePath($sessionId));
        $service->deletePlayerVolumeFile($player, self::legacyVolumeCachePath($sessionId));

        \Log::info('ObjectCache rimosso dal volume player', [
            'session_id' => $sessionId,
            'player_id' => $player->id,
            'path' => self::volumeCachePath($sessionId),
        ]);
    }

    private static function decodePlayerStorage(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('Impossibile leggere ObjectCache dal volume del player: ' . $e->getMessage(), 0, $e);
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
