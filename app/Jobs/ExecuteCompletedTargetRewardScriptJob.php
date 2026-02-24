<?php

namespace App\Jobs;

use App\Models\Player;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExecuteCompletedTargetRewardScriptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $targetPlayerId,
        public int $playerId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $player = Player::find($this->playerId);
        if (!$player) {
            return;
        }

        $filename = $this->targetPlayerId . '.php';

        try {
            if (!Storage::disk('rewards_player')->exists($filename)) {
                return;
            }

            $scriptContent = Storage::disk('rewards_player')->get($filename);
            $player_id = (int) $player->id;
            $runtimeScript = preg_replace('/^\s*<\?php/', '', $scriptContent) ?? $scriptContent;
            $runtimeScript = preg_replace('/\?>\s*$/', '', $runtimeScript) ?? $runtimeScript;
            $runtimeScript = "namespace App\\Rewards\\Runtime;\n" . $runtimeScript;

            eval($runtimeScript);
        } catch (\Throwable $e) {
            Log::error('Error executing rewards_player script', [
                'player_id' => $player->id,
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
