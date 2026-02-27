<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\GameController;
use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TestTilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:tiles
                            {player_id=73 : Player ID}
                            {--tile_i= : Filter by tile_i}
                            {--tile_j= : Filter by tile_j}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test command to retrieve tiles (with entity/element) for a player birth region';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $playerId = (int) $this->argument('player_id');
        $player = Player::query()->find($playerId);

        if (!$player) {
            $this->error("Player {$playerId} non trovato.");
            return self::FAILURE;
        }

        if (empty($player->birth_region_id)) {
            $this->error("Player {$playerId} non ha birth_region_id.");
            return self::FAILURE;
        }

        $request = Request::create('/api/auth/game/get_tiles_by_birth_region', 'POST', [
            'birth_region_id' => (int) $player->birth_region_id,
        ]);

        /** @var GameController $controller */
        $controller = app(GameController::class);
        $response = $controller->getTilesByBirthRegion($request);
        $payload = $response->getData(true);

        if (!(bool) ($payload['success'] ?? false)) {
            $this->error('Richiesta tile fallita.');
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));
            return self::FAILURE;
        }

        $tiles = collect($payload['tiles'] ?? []);
        $tileI = $this->option('tile_i');
        $tileJ = $this->option('tile_j');

        if ($tileI !== null || $tileJ !== null) {
            if ($tileI === null || $tileJ === null) {
                $this->error('Per filtrare un tile devi passare sia --tile_i che --tile_j.');
                return self::FAILURE;
            }

            $tile = $tiles
                ->first(fn ($t) => (int) ($t['i'] ?? -1) === (int) $tileI && (int) ($t['j'] ?? -1) === (int) $tileJ);

            if (!$tile) {
                $this->warn("Tile ({$tileI}, {$tileJ}) non trovato.");
                return self::SUCCESS;
            }

            $this->line(json_encode($tile, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info("Player: {$playerId}");
        $this->info("Birth region: {$player->birth_region_id}");
        $this->info('Totale tile: ' . $tiles->count());
        $this->info('Tile con entity: ' . $tiles->filter(fn ($t) => !empty($t['entity']))->count());
        $this->info('Tile con element: ' . $tiles->filter(fn ($t) => !empty($t['element']))->count());

        return self::SUCCESS;
    }
}

