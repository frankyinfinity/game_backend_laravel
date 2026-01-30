<?php

namespace App\Helper;

use App\Jobs\StartPlayerContainersJob;
use App\Models\BirthRegion;
use App\Models\DrawRequest;
use App\Models\Player;
use App\Models\Tile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Psy\Util\Str;

class Helper
{

    public const TILE_SIZE = 40;
    public const DEFAULT_FONT_SIZE = 16;
    public const DEFAULT_FONT_FAMILY = 'Consolas';

    const DRAW_REQUEST_TYPE_DRAW = 'draw';
    const DRAW_REQUEST_TYPE_UPDATE = 'update';
    const DRAW_REQUEST_TYPE_CLEAR = 'clear';

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

    public static function calculatePathFinding(array $grid): ?array {

        $rows = count($grid);
        $cols = count($grid[0]);
        $directions = [[-1,0], [1,0], [0,-1], [0,1]]; // up, down, left, right

        $start = $end = null;

        // Find 'A' and 'B'
        for ($i=0; $i<$rows; $i++) {
            for ($j=0; $j<$cols; $j++) {
                if ($grid[$i][$j] === 'A') $start = [$i,$j];
                if ($grid[$i][$j] === 'B') $end = [$i,$j];
            }
        }

        if (!$start || !$end) return null; // no start or end

        $visited = array_fill(0, $rows, array_fill(0, $cols, false));
        $parent = array_fill(0, $rows, array_fill(0, $cols, null));

        $queue = [];
        $queue[] = $start;
        $visited[$start[0]][$start[1]] = true;

        while (!empty($queue)) {
            [$r, $c] = array_shift($queue);

            if ($r === $end[0] && $c === $end[1]) {
                // reconstruct path by backtracking parents
                $path = [];
                while ($r !== null && $c !== null) {
                    array_unshift($path, [$r, $c]);
                    $p = $parent[$r][$c];
                    if ($p === null) break;
                    [$r, $c] = $p;
                }
                return $path;
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
                    $parent[$nr][$nc] = [$r, $c];
                    $queue[] = [$nr, $nc];
                }
            }
        }

        return null;

    }

    public static function generateSessionIdPlayer(Player $player): string
    {

        //Reset
        if($player->actual_session_id !== null) {
            \App\Custom\Manipulation\ObjectCache::clear($player->actual_session_id);
        }

        //Set
        $session_id = uniqid(\Illuminate\Support\Str::random(16), true);
        $player->update(['actual_session_id' => $session_id]);

        // Dispatch job to start containers
        StartPlayerContainersJob::dispatch($player);

        return $session_id;

    }

    /**
     * Get all coordinates of tiles with a specific tile ID in a birth region
     * 
     * @param int $birthRegionId The ID of the birth region
     * @param int $tileId The ID of the tile to search for
     * @return array Array of coordinates ['x' => j, 'y' => i] for each matching tile
     */
    public static function getTileCoordinates(int $birthRegionId, int $tileId): array
    {
        $birthRegion = BirthRegion::find($birthRegionId);
        if (!$birthRegion) {
            return [];
        }

        $tiles = self::getBirthRegionTiles($birthRegion);
        $coordinates = [];

        // Search in the tiles collection for matching tile IDs
        foreach ($tiles as $tile) {
            if (isset($tile['tile']['id']) && $tile['tile']['id'] == $tileId) {
                $coordinates[] = [
                    'i' => $tile['i'],
                    'j' => $tile['j'],
                    'x' => $tile['j'] * self::TILE_SIZE,
                    'y' => $tile['i'] * self::TILE_SIZE,
                ];
            }
        }

        // Also check default tile from birth climate
        if ($birthRegion->birthClimate && $birthRegion->birthClimate->default_tile_id == $tileId) {
            // Add coordinates for tiles that are not explicitly set (they use default)
            for ($i = 0; $i < $birthRegion->height; $i++) {
                for ($j = 0; $j < $birthRegion->width; $j++) {
                    $existingTile = $tiles->where('i', $i)->where('j', $j)->first();
                    if ($existingTile === null) {
                        $coordinates[] = [
                            'i' => $i,
                            'j' => $j,
                            'x' => $j * self::TILE_SIZE,
                            'y' => $i * self::TILE_SIZE,
                        ];
                    }
                }
            }
        }

        return $coordinates;
    }

    /**
     * Returns true with the given percentage probability
     * 
     * @param float $percentage Percentage chance (0-100) of returning true
     * @return bool True with the given probability, false otherwise
     */
    public static function chance(float $percentage): bool
    {
        if ($percentage <= 0) {
            return false;
        }
        if ($percentage >= 100) {
            return true;
        }
        
        // Generate random number between 0 and 100 (with decimal precision)
        $random = mt_rand(0, 10000) / 100;
        
        return $random < $percentage;
    }

}
