<?php

namespace App\Helper;

class Helper
{

    public const TILE_SIZE = 40;
    public static function getTileSize(): int
    {
        return self::TILE_SIZE;
    }

    public const DEFAULT_FONT_SIZE = 16;
    public static function getDefaultFontSize(): int
    {
        return self::DEFAULT_FONT_SIZE;
    }

    private const DEFAULT_FONT_FAMILY = 'Consolas';
    public static function getDefaultFontFamily(): string
    {
        return self::DEFAULT_FONT_FAMILY;
    }

    public static function setCommonJsCode($code, $name): array|string
    {

        $code = str_replace('<script>', '', $code);
        $code = str_replace('</script>', '', $code);
        $code = str_replace('</script>', '', $code);
        return str_replace('__name__', $name, $code);

    }

}
