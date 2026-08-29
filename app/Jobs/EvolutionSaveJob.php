<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvolutionSaveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    /**
     * @param int   $playerId ID del player proprietario del container Evolution
     * @param array $payload  Tutto il JSON ricevuto dall'API evolution/save
     */
    public function __construct(
        public int $playerId,
        public array $payload
    ) {}

    public function handle(): void
    {
        \Log::info('[EvolutionSaveJob] JSON ricevuto dal container Evolution (evolution/save)', [
            'player_id' => $this->playerId,
            'payload' => $this->payload,
        ]);
    }
}
