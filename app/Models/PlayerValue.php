<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerValue extends Model
{
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_FLOAT = 'float';
    public const TYPE_STRING = 'string';
    public const TYPE_JSON = 'json';

    public const KEY_MOVEMENT = 'movement';
    public const KEY_CONSUME = 'consume';
    public const KEY_ATTACK = 'attack';
    public const KEY_DIVISION = 'division';
    public const KEY_DIVISION_COST = 'division_cost';
    public const KEY_LIFEPOINT_GENERATE_NEW_ENTITY = 'lifepoint_generate_new_entity';

    public const BOOLEAN_KEYS = [
        self::KEY_MOVEMENT,
        self::KEY_CONSUME,
        self::KEY_ATTACK,
        self::KEY_DIVISION,
    ];

    public const INTEGER_KEYS = [
        self::KEY_DIVISION_COST => 50,
        self::KEY_LIFEPOINT_GENERATE_NEW_ENTITY => 40,
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public static function ensureDefaultsForPlayer(int $playerId): void
    {
        foreach (self::BOOLEAN_KEYS as $key) {
            self::query()->updateOrCreate(
                ['player_id' => $playerId, 'key' => $key],
                [
                    'data_type' => self::TYPE_BOOLEAN,
                    'value' => '0',
                ]
            );
        }

        foreach (self::INTEGER_KEYS as $key => $integerValue) {
            self::query()->updateOrCreate(
                ['player_id' => $playerId, 'key' => $key],
                [
                    'data_type' => self::TYPE_INTEGER,
                    'value' => (string) $integerValue,
                ]
            );
        }
    }

    public static function setFlag(int $playerId, string $key, bool $value): void
    {
        if (!in_array($key, self::BOOLEAN_KEYS, true)) {
            return;
        }

        self::query()->updateOrCreate(
            ['player_id' => $playerId, 'key' => $key],
            [
                'data_type' => self::TYPE_BOOLEAN,
                'value' => $value ? '1' : '0',
            ]
        );
    }

    public static function setValue(int $playerId, string $key, mixed $value, string $dataType = self::TYPE_STRING): void
    {
        self::query()->updateOrCreate(
            ['player_id' => $playerId, 'key' => $key],
            [
                'data_type' => $dataType,
                'value' => self::encodeValue($value, $dataType),
            ]
        );
    }

    public static function hasAnyActive(int $playerId, array $keys): bool
    {
        $keys = array_values(array_filter(array_unique($keys), function ($key) {
            return is_string($key) && in_array($key, self::BOOLEAN_KEYS, true);
        }));

        if (empty($keys)) {
            return false;
        }

        foreach ($keys as $key) {
            self::query()->updateOrCreate(
                ['player_id' => $playerId, 'key' => $key],
                [
                    'data_type' => self::TYPE_BOOLEAN,
                    'value' => '0',
                ]
            );
        }

        $values = self::query()
            ->where('player_id', $playerId)
            ->whereIn('key', $keys)
            ->get(['data_type', 'value']);

        foreach ($values as $row) {
            if ((bool) self::decodeValue($row->value, (string) $row->data_type)) {
                return true;
            }
        }

        return false;
    }

    public static function getIntegerValue(int $playerId, string $key): int
    {
        $defaultValue = self::INTEGER_KEYS[$key] ?? 0;

        $row = self::query()->firstOrCreate(
            ['player_id' => $playerId, 'key' => $key],
            [
                'data_type' => self::TYPE_INTEGER,
                'value' => (string) $defaultValue,
            ]
        );

        return (int) self::decodeValue($row->value, (string) $row->data_type);
    }

    public static function encodeValue(mixed $value, string $dataType): ?string
    {
        return match ($dataType) {
            self::TYPE_BOOLEAN => $value ? '1' : '0',
            self::TYPE_INTEGER => (string) ((int) $value),
            self::TYPE_FLOAT => (string) ((float) $value),
            self::TYPE_JSON => json_encode($value),
            default => $value === null ? null : (string) $value,
        };
    }

    public static function decodeValue(?string $value, string $dataType): mixed
    {
        return match ($dataType) {
            self::TYPE_BOOLEAN => in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true),
            self::TYPE_INTEGER => (int) $value,
            self::TYPE_FLOAT => (float) $value,
            self::TYPE_JSON => $value === null ? null : json_decode($value, true),
            default => $value,
        };
    }
}
