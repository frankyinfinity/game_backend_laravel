<?php

namespace App\Observers;

use App\Models\Player;
use App\Jobs\InitializePlayerJob;
use App\Models\Age;
use App\Models\AgePlayer;
use App\Models\Phase;
use App\Models\PhasePlayer;
use App\Models\PhaseColumn;
use App\Models\PhaseColumnPlayer;
use App\Models\Target;
use App\Models\TargetPlayer;
use App\Models\TargetHasScore;
use App\Models\TargetHasScorePlayer;
use App\Models\TargetLink;
use App\Models\TargetLinkPlayer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PlayerObserver
{
    /**
     * Handle the Player "created" event.
     */
    public function created(Player $player): void
    {
        // Clone the objective structure for the player
        $this->cloneObjectiveStructure($player);
        
        // Check if there's registration data stored on the player
        if (isset($player->registrationData)) {
            InitializePlayerJob::dispatch($player, $player->registrationData);
        }
    }

    /**
     * Clone the entire objective structure for a new player.
     */
    protected function cloneObjectiveStructure(Player $player): void
    {
        // Maps to track original IDs to player IDs
        $ageMap = [];
        $phaseMap = [];
        $phaseColumnMap = [];
        $targetMap = [];

        // Clone Ages
        $ages = Age::orderBy('order')->get();
        $isFirstAge = true;
        foreach ($ages as $age) {
            $agePlayer = AgePlayer::create([
                'player_id' => $player->id,
                'age_id' => $age->id,
                'name' => $age->name,
                'order' => $age->order,
                'state' => $isFirstAge ? AgePlayer::STATE_UNLOCKED : AgePlayer::STATE_LOCKED,
            ]);
            $ageMap[$age->id] = $agePlayer->id;
            $isFirstAge = false;
        }

        // Clone Phases - unlock only the very first phase of the first age
        $firstAgeId = Age::orderBy('order')->value('id');
        $firstPhaseId = Phase::where('age_id', $firstAgeId)->orderBy('order')->value('id');
        $phases = Phase::orderBy('order')->get();
        foreach ($phases as $phase) {
            $isFirstAgePhase = ($phase->id === $firstPhaseId);
            $phasePlayer = PhasePlayer::create([
                'player_id' => $player->id,
                'age_player_id' => $ageMap[$phase->age_id],
                'phase_id' => $phase->id,
                'name' => $phase->name,
                'height' => $phase->height,
                'order' => $phase->order,
                'state' => $isFirstAgePhase ? PhasePlayer::STATE_UNLOCKED : PhasePlayer::STATE_LOCKED,
            ]);
            $phaseMap[$phase->id] = $phasePlayer->id;
        }

        // Clone PhaseColumns
        $phaseColumns = PhaseColumn::all();
        foreach ($phaseColumns as $phaseColumn) {
            $phaseColumnPlayer = PhaseColumnPlayer::create([
                'player_id' => $player->id,
                'phase_player_id' => $phaseMap[$phaseColumn->phase_id],
                'phase_column_id' => $phaseColumn->id,
                'uid' => $phaseColumn->uid,
            ]);
            $phaseColumnMap[$phaseColumn->id] = $phaseColumnPlayer->id;
        }

        // Clone Targets - unlock only the very first target of first phase in first age
        $firstPhaseColumnId = PhaseColumn::where('phase_id', $firstPhaseId)
            ->orderBy('id')
            ->value('id');
        $firstTargetId = Target::where('phase_column_id', $firstPhaseColumnId)
            ->orderBy('slot')
            ->orderBy('id')
            ->value('id');
        
        $targets = Target::all();
        foreach ($targets as $target) {
            // Only the first target of first phase in first age is unlocked
            $isFirstPhaseTarget = ($target->id === $firstTargetId);
            $targetPlayer = TargetPlayer::create([
                'player_id' => $player->id,
                'phase_column_player_id' => $phaseColumnMap[$target->phase_column_id],
                'target_id' => $target->id,
                'slot' => $target->slot,
                'title' => $target->title,
                'description' => $target->description,
                'state' => $isFirstPhaseTarget ? TargetPlayer::STATE_UNLOCKED : TargetPlayer::STATE_LOCKED,
            ]);
            $targetMap[$target->id] = $targetPlayer->id;

            // Clone reward script from template target disk to player target disk
            $sourceFilename = $target->id . '.php';
            $destinationFilename = $targetPlayer->id . '.php';
            try {
                if (Storage::disk('rewards')->exists($sourceFilename)) {
                    $rewardContent = Storage::disk('rewards')->get($sourceFilename);
                    Storage::disk('rewards_player')->put($destinationFilename, $rewardContent);
                }
            } catch (\Throwable $e) {
                Log::warning('Unable to clone target reward file for player target', [
                    'player_id' => $player->id,
                    'target_id' => $target->id,
                    'target_player_id' => $targetPlayer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clone TargetHasScores
        $targetHasScores = TargetHasScore::all();
        foreach ($targetHasScores as $targetHasScore) {
            TargetHasScorePlayer::create([
                'player_id' => $player->id,
                'target_player_id' => $targetMap[$targetHasScore->target_id],
                'score_id' => $targetHasScore->score_id,
                'value' => $targetHasScore->value,
            ]);
        }

        // Clone TargetLinks
        $targetLinks = TargetLink::all();
        foreach ($targetLinks as $targetLink) {
            TargetLinkPlayer::create([
                'player_id' => $player->id,
                'from_target_player_id' => $targetMap[$targetLink->from_target_id],
                'to_target_player_id' => $targetMap[$targetLink->to_target_id],
            ]);
        }
    }

    /**
     * Handle the Player "updated" event.
     */
    public function updated(Player $player): void
    {
        //
    }

    /**
     * Handle the Player "deleted" event.
     */
    public function deleted(Player $player): void
    {
        //
    }

    /**
     * Handle the Player "restored" event.
     */
    public function restored(Player $player): void
    {
        //
    }

    /**
     * Handle the Player "force deleted" event.
     */
    public function forceDeleted(Player $player): void
    {
        //
    }
}
