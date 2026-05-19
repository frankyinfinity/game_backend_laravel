<?php

namespace App\Jobs;

use App\Models\Region;
use App\Models\Tile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class GenerateRegionImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Region $region
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $region = $this->region->fresh();
        if (!$region) {
            return;
        }

        // Load map data
        $map = [];
        if ($region->filename !== null && Storage::disk('regions')->exists($region->id . '/' . $region->filename)) {
            $jsonContent = Storage::disk('regions')->get($region->id . '/' . $region->filename);
            $map = json_decode($jsonContent, true);
        }

        // If no map exists, create a default map with all positions as default tile
        if (empty($map)) {
            $defaultTileId = $region->climate->defaultTile->id ?? null;
            if (!$defaultTileId) {
                return; // No default tile
            }
            $width = $region->width;
            $height = $region->height;
            $map = [];
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $map[$y][$x] = $defaultTileId;
                }
            }
            // Save the default map
            $filename = 'map_' . time() . '.json';
            $jsonData = json_encode($map, JSON_PRETTY_PRINT);
            Storage::disk('regions')->put($region->id . '/' . $filename, $jsonData);
            $region->filename = $filename;
            $region->save();
        }

        $width = $region->width;
        $height = $region->height;
        $tileSize = \App\Helper\Helper::TILE_SIZE;

        // Create canvas using Intervention Image v4
        $manager = ImageManager::usingDriver(Driver::class);
        $canvas = $manager->createImage($width * $tileSize, $height * $tileSize);

        // For each position in map
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $tileId = $map[$y][$x] ?? null;
                if ($tileId) {
                    $tile = Tile::find($tileId);
                    if ($tile && Storage::disk('tile')->exists($tile->id . '.png')) {
                        $tileImagePath = Storage::disk('tile')->path($tile->id . '.png');
                        $tileImage = $manager->decode($tileImagePath);
                        // Resize the tile to fill the grid slot exactly and avoid borders/gaps
                        $tileImage->resize($tileSize, $tileSize);
                        $canvas->insert($tileImage, $x * $tileSize, $y * $tileSize, 'top-left');
                    }
                }
            }
        }

        // Generate unique id
        $uid = time() . '_' . $region->id;

        $originalFilename = 'original_' . $uid . '.png';
        $modifiedFilename = 'modified_' . $uid . '.png';

        // Make sure the directory exists before saving
        if (!Storage::disk('map_tile')->exists((string) $region->id)) {
            Storage::disk('map_tile')->makeDirectory((string) $region->id);
        }

        // Save original_uid.png
        $canvas->save(Storage::disk('map_tile')->path($region->id . '/' . $originalFilename));

        // Save modified_uid.png (identical for now)
        $canvas->save(Storage::disk('map_tile')->path($region->id . '/' . $modifiedFilename));

        // Update region with filenames and set state to STATE_GENERATED
        $region->update([
            'original_image' => $originalFilename,
            'modified_image' => $modifiedFilename,
            'state' => Region::STATE_GENERATED,
        ]);
    }
}
