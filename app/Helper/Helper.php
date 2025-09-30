<?php

namespace App\Helper;

use App\Models\BirthRegion;
use App\Models\Tile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

    public static function getBirthRegionTiles(BirthRegion $birthRegion): \Illuminate\Support\Collection
    {

        $tiles = [];
        if($birthRegion->filename !== null) {
            $jsonContent = Storage::disk('birth_regions')->get($birthRegion->id.'/'.$birthRegion->filename);
            $tiles = json_decode($jsonContent, true);
        }
        return collect($tiles);

    }

    public static function getMapSolidTiles(\Illuminate\Support\Collection $tiles, BirthRegion $birthRegion): array
    {

        $mapSolidTiles = [];

        for ($i = 0; $i < $birthRegion->height; $i++) {
            $mapSolidTiles[] = [];
            for ($j = 0; $j < $birthRegion->width; $j++) {

                $tile = $birthRegion->birthClimate->default_tile;
                $searchTile = $tiles->where('i', $i)->where('j', $j)->first();
                if ($searchTile !== null) {
                    $tile = $searchTile['tile'];
                }

                $value = $tile['type'] == Tile::TYPE_SOLID ? 'X' : '0';
                $mapSolidTiles[sizeof($mapSolidTiles)-1][] = $value;

            }
        }

        return $mapSolidTiles;

    }

    public static function calculatePathFinding(array $grid) {

        $rows = count($grid);
        $cols = count($grid[0]);
        $directions = [[-1, 0], [1, 0], [0, -1], [0, 1]]; // up, down, left, right

        $start = $end = null;

        // Find 'A' and 'B'
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                if ($grid[$i][$j] === 'A') $start = [$i, $j];
                if ($grid[$i][$j] === 'B') $end = [$i, $j];
            }
        }

        if (!$start || !$end) {
            return null; // A or B not found
        }

        // BFS queue: each element is [row, col, path_so_far]
        $queue = [[$start[0], $start[1], []]];
        $visited = array_fill(0, $rows, array_fill(0, $cols, false));
        $visited[$start[0]][$start[1]] = true;

        while (!empty($queue)) {
            [$r, $c, $path] = array_shift($queue);
            $path[] = [$r, $c];

            if ($r === $end[0] && $c === $end[1]) {
                return $path; // path found
            }

            foreach ($directions as [$dr, $dc]) {
                $nr = $r + $dr;
                $nc = $c + $dc;

                if (
                    $nr >= 0 && $nr < $rows &&
                    $nc >= 0 && $nc < $cols &&
                    !$visited[$nr][$nc] &&
                    ($grid[$nr][$nc] === '0' || $grid[$nr][$nc] === 'B')
                ) {
                    $visited[$nr][$nc] = true;
                    $queue[] = [$nr, $nc, $path];
                }
            }
        }

        return null; // no path found

    }

}
