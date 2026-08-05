<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Player;
use Illuminate\Console\Command;

class SendAlertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alert:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invia un alert al container WebSocket del player specificato';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $playerId = $this->ask('Inserisci l\'ID del player');
        $title = $this->ask('Inserisci il titolo dell\'alert');
        $body = $this->ask('Inserisci il corpo del messaggio');
        $type = $this->choice('Seleziona il tipo di alert', ['info', 'warning', 'error', 'success'], 'info');

        // Mappa da stringa a costante
        $typeMap = [
            'info' => Alert::TYPE_INFO,
            'warning' => Alert::TYPE_WARNING,
            'error' => Alert::TYPE_ERROR,
            'success' => Alert::TYPE_SUCCESS,
        ];

        $typeValue = $typeMap[$type] ?? Alert::TYPE_INFO;

        // Recupera il player
        $player = Player::find($playerId);
        if (!$player) {
            $this->error("Player con ID {$playerId} non trovato.");
            return self::FAILURE;
        }

        // Crea l'alert nel database (l'observer invierà il WebSocket automaticamente)
        Alert::create([
            'player_id' => $player->id,
            'title' => $title,
            'body' => $body,
            'type' => $typeValue,
        ]);

        $this->info("Alert creato con successo per il player {$playerId}.");
        return self::SUCCESS;
    }
}
