<?php

namespace App\Helper;

class Helper
{

    const TILE_SIZE = 40;
    public static function getTileSize(): int
    {
        return self::TILE_SIZE;
    }

    public static function setCommonJsCode($code, $name): array|string
    {

        $code = str_replace('<script>', '', $code);
        $code = str_replace('</script>', '', $code);
        $code = str_replace('</script>', '', $code);
        return str_replace('__name__', $name, $code);

    }

}
