<?php

namespace App\Custom\Manipulation;

use InvalidArgumentException;

class ObjectNormalizer
{
    public static function normalize(array|object $object): array
    {
        if (is_array($object)) {
            self::assertUid($object);
            return $object;
        }

        if (!method_exists($object, 'buildJson')) {
            throw new InvalidArgumentException('Object must be an array or expose buildJson().');
        }

        $normalized = $object->buildJson();
        if (!is_array($normalized)) {
            throw new InvalidArgumentException('buildJson() must return an array.');
        }

        self::assertUid($normalized);

        return $normalized;
    }

    private static function assertUid(array $object): void
    {
        $uid = $object['uid'] ?? null;
        if (!is_string($uid) || $uid === '') {
            throw new InvalidArgumentException('Manipulated objects must contain a non-empty uid.');
        }
    }
}
