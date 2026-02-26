<?php

namespace App\Custom\Draw\Support;

use App\Helper\Helper;

class ScrollGroup
{
    public static function attach(array $item, string $group = Helper::MAP_SCROLL_GROUP_MAIN): array
    {
        if (!isset($item['attributes']) || !is_array($item['attributes'])) {
            $item['attributes'] = [];
        }
        $item['attributes']['scroll_group'] = $group;

        return $item;
    }

    public static function attachMany(array $items, string $group = Helper::MAP_SCROLL_GROUP_MAIN): array
    {
        $tagged = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $tagged[] = self::attach($item, $group);
            } else {
                $tagged[] = $item;
            }
        }

        return $tagged;
    }
}
