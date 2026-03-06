<?php

namespace App\Jobs;

use App\Custom\Draw\Complex\ModalDraw;
use App\Custom\Draw\Complex\Objective\ObjectiveTreeDraw;
use App\Custom\Draw\Complex\ScoreDraw;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Models\AgePlayer;
use App\Models\DrawRequest;
use App\Models\PhaseColumnPlayer;
use App\Models\PhasePlayer;
use App\Models\Player;
use App\Models\PlayerHasScore;
use App\Models\PlayerValue;
use App\Models\TargetLinkPlayer;
use App\Models\TargetPlayer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use function GuzzleHttp\json_encode;

class CheckObjectiveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $payload
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $request = new Request($this->payload);
        $playerId = (int) $request->player_id;

        if ($playerId <= 0) {
            return;
        }

        $player = Player::find($playerId);
        if (!$player) {
            return;
        }

        if (PlayerValue::hasAnyActive($playerId, [
            PlayerValue::KEY_MOVEMENT,
            PlayerValue::KEY_CONSUME,
            PlayerValue::KEY_ATTACK
        ])) {
            return;
        }

        $inProgressTargets = TargetPlayer::query()
            ->where('player_id', $playerId)
            ->where('state', TargetPlayer::STATE_IN_PROGRESS)
            ->with([
                'targetHasScorePlayers',
                'outgoingLinks.toTargetPlayer',
                'phaseColumnPlayer',
            ])
            ->orderBy('id')
            ->get();

        if ($inProgressTargets->isEmpty()) {
            return;
        }

        $playerScores = PlayerHasScore::query()
            ->where('player_id', $playerId)
            ->get()
            ->keyBy('score_id');

        $completedTargetIds = [];
        $unlockedTargetIds = [];
        $completedPhaseIds = [];
        $unlockedPhaseIds = [];
        $completedAgeIds = [];
        $unlockedAgeIds = [];
        $updatedScores = [];
        $skippedTargets = [];
        $objectiveTreeChanged = false;

        DB::transaction(function () use (
            $playerId,
            $inProgressTargets,
            &$playerScores,
            &$completedTargetIds,
            &$unlockedTargetIds,
            &$completedPhaseIds,
            &$unlockedPhaseIds,
            &$completedAgeIds,
            &$unlockedAgeIds,
            &$updatedScores,
            &$skippedTargets,
            &$objectiveTreeChanged
        ) {
            $unlockFirstTargetForPhase = function (int $phasePlayerId) use ($playerId, &$unlockedTargetIds, &$objectiveTreeChanged) {
                $firstPhaseColumnPlayer = PhaseColumnPlayer::query()
                    ->where('player_id', $playerId)
                    ->where('phase_player_id', $phasePlayerId)
                    ->orderBy('phase_column_id')
                    ->orderBy('id')
                    ->first();

                if (!$firstPhaseColumnPlayer) {
                    return;
                }

                $firstColumnTargets = TargetPlayer::query()
                    ->where('player_id', $playerId)
                    ->where('phase_column_player_id', $firstPhaseColumnPlayer->id)
                    ->orderBy('slot')
                    ->orderBy('id')
                    ->get();

                if ($firstColumnTargets->isEmpty()) {
                    return;
                }

                foreach ($firstColumnTargets as $target) {
                    if ($target->state === TargetPlayer::STATE_LOCKED) {
                        $target->update([
                            'state' => TargetPlayer::STATE_UNLOCKED,
                        ]);
                        $unlockedTargetIds[] = $target->id;
                        $objectiveTreeChanged = true;
                    }
                }
            };

            foreach ($inProgressTargets as $targetPlayer) {
                $requirements = $targetPlayer->targetHasScorePlayers;
                $canComplete = true;
                $missingScores = [];

                foreach ($requirements as $requirement) {
                    $scoreId = (int) $requirement->score_id;
                    $requiredValue = (int) $requirement->value;
                    $availableValue = (int) ($playerScores[$scoreId]->value ?? 0);

                    if ($availableValue < $requiredValue) {
                        $canComplete = false;
                        $missingScores[] = [
                            'score_id' => $scoreId,
                            'required' => $requiredValue,
                            'available' => $availableValue,
                        ];
                    }
                }

                if (!$canComplete) {
                    $skippedTargets[] = [
                        'target_player_id' => $targetPlayer->id,
                        'missing_scores' => $missingScores,
                    ];
                    continue;
                }

                foreach ($requirements as $requirement) {
                    $scoreId = (int) $requirement->score_id;
                    $requiredValue = (int) $requirement->value;
                    $playerScore = $playerScores[$scoreId] ?? null;

                    if (!$playerScore) {
                        $playerScore = PlayerHasScore::query()->create([
                            'player_id' => $playerId,
                            'score_id' => $scoreId,
                            'value' => 0,
                        ]);
                        $playerScores[$scoreId] = $playerScore;
                        $objectiveTreeChanged = true;
                    }

                    $newValue = max(0, (int) $playerScore->value - $requiredValue);
                    $playerScore->update(['value' => $newValue]);
                    $playerScores[$scoreId] = $playerScore->fresh();

                    $updatedScores[$scoreId] = $newValue;
                    $objectiveTreeChanged = true;
                }

                $targetPlayer->update([
                    'state' => TargetPlayer::STATE_COMPLETED,
                ]);
                $completedTargetIds[] = $targetPlayer->id;
                $objectiveTreeChanged = true;

                foreach ($targetPlayer->outgoingLinks as $link) {
                    $nextTarget = $link->toTargetPlayer;
                    if (
                        $nextTarget
                        && (int) $nextTarget->player_id === $playerId
                        && $nextTarget->state === TargetPlayer::STATE_LOCKED
                    ) {
                        $hasIncompletePrerequisites = TargetLinkPlayer::query()
                            ->where('player_id', $playerId)
                            ->where('to_target_player_id', $nextTarget->id)
                            ->whereHas('fromTargetPlayer', function ($q) use ($playerId) {
                                $q->where('player_id', $playerId)
                                  ->where('state', '!=', TargetPlayer::STATE_COMPLETED);
                            })
                            ->exists();

                        if (!$hasIncompletePrerequisites) {
                            $nextTarget->update([
                                'state' => TargetPlayer::STATE_UNLOCKED,
                            ]);
                            $unlockedTargetIds[] = $nextTarget->id;
                            $objectiveTreeChanged = true;
                        }
                    }
                }
            }

            $phasePlayers = PhasePlayer::query()
                ->where('player_id', $playerId)
                ->with([
                    'phase',
                    'agePlayer',
                    'phaseColumnPlayers.targetPlayers',
                ])
                ->get();

            foreach ($phasePlayers as $phasePlayer) {
                $allTargets = collect();
                foreach ($phasePlayer->phaseColumnPlayers as $phaseColumnPlayer) {
                    $allTargets = $allTargets->merge($phaseColumnPlayer->targetPlayers);
                }

                if ($allTargets->isEmpty()) {
                    continue;
                }

                $allCompleted = $allTargets->every(function ($target) {
                    return $target->state === TargetPlayer::STATE_COMPLETED;
                });
                $hasUnlockedOrBetter = $allTargets->contains(function ($target) {
                    return in_array($target->state, [
                        TargetPlayer::STATE_UNLOCKED,
                        TargetPlayer::STATE_IN_PROGRESS,
                        TargetPlayer::STATE_COMPLETED,
                    ], true);
                });

                $newPhaseState = PhasePlayer::STATE_LOCKED;
                if ($allCompleted) {
                    $newPhaseState = PhasePlayer::STATE_COMPLETED;
                } elseif ($hasUnlockedOrBetter) {
                    $newPhaseState = PhasePlayer::STATE_UNLOCKED;
                }

                if ($phasePlayer->state !== $newPhaseState) {
                    $phasePlayer->update(['state' => $newPhaseState]);
                    $phasePlayer->state = $newPhaseState;
                    $objectiveTreeChanged = true;
                }
            }

            $phasePlayers = PhasePlayer::query()
                ->where('player_id', $playerId)
                ->with(['phase', 'agePlayer'])
                ->get();

            $phaseByAge = $phasePlayers
                ->filter(function ($phasePlayer) {
                    return $phasePlayer->phase !== null;
                })
                ->groupBy(function ($phasePlayer) {
                    return (int) $phasePlayer->phase->age_id;
                });

            foreach ($phaseByAge as $phaseGroup) {
                $orderedPhases = $phaseGroup->sortBy(function ($phasePlayer) {
                    return (int) $phasePlayer->phase->order;
                })->values();

                $phaseCount = $orderedPhases->count();
                for ($i = 0; $i < $phaseCount - 1; $i++) {
                    $current = $orderedPhases[$i];
                    $next = $orderedPhases[$i + 1];

                    if (
                        $current->state === PhasePlayer::STATE_COMPLETED
                        && $next->state === PhasePlayer::STATE_LOCKED
                    ) {
                        $next->update(['state' => PhasePlayer::STATE_UNLOCKED]);
                        $next->state = PhasePlayer::STATE_UNLOCKED;
                        $unlockedPhaseIds[] = $next->id;
                        $unlockFirstTargetForPhase((int) $next->id);
                        $objectiveTreeChanged = true;
                    }
                }
            }

            $phasePlayers = PhasePlayer::query()
                ->where('player_id', $playerId)
                ->with(['phase'])
                ->get();

            $agePlayers = AgePlayer::query()
                ->where('player_id', $playerId)
                ->with(['age'])
                ->get();

            foreach ($agePlayers as $agePlayer) {
                if (!$agePlayer->age) {
                    continue;
                }

                $phasesOfAge = $phasePlayers->filter(function ($phasePlayer) use ($agePlayer) {
                    return $phasePlayer->phase && (int) $phasePlayer->phase->age_id === (int) $agePlayer->age_id;
                });

                if ($phasesOfAge->isEmpty()) {
                    continue;
                }

                $allPhasesCompleted = $phasesOfAge->every(function ($phasePlayer) {
                    return $phasePlayer->state === PhasePlayer::STATE_COMPLETED;
                });
                $hasPhaseUnlockedOrCompleted = $phasesOfAge->contains(function ($phasePlayer) {
                    return in_array($phasePlayer->state, [
                        PhasePlayer::STATE_UNLOCKED,
                        PhasePlayer::STATE_COMPLETED,
                    ], true);
                });

                $newAgeState = AgePlayer::STATE_LOCKED;
                if ($allPhasesCompleted) {
                    $newAgeState = AgePlayer::STATE_COMPLETED;
                } elseif ($hasPhaseUnlockedOrCompleted) {
                    $newAgeState = AgePlayer::STATE_UNLOCKED;
                }

                if ($agePlayer->state !== $newAgeState) {
                    $agePlayer->update(['state' => $newAgeState]);
                    $agePlayer->state = $newAgeState;
                    $objectiveTreeChanged = true;
                }
            }

            $agePlayers = AgePlayer::query()
                ->where('player_id', $playerId)
                ->with(['age'])
                ->get()
                ->filter(function ($agePlayer) {
                    return $agePlayer->age !== null;
                })
                ->sortBy(function ($agePlayer) {
                    return (int) $agePlayer->age->order;
                })
                ->values();

            $ageCount = $agePlayers->count();
            for ($i = 0; $i < $ageCount - 1; $i++) {
                $currentAge = $agePlayers[$i];
                $nextAge = $agePlayers[$i + 1];

                if (
                    $currentAge->state === AgePlayer::STATE_COMPLETED
                    && $nextAge->state === AgePlayer::STATE_LOCKED
                ) {
                    $nextAge->update(['state' => AgePlayer::STATE_UNLOCKED]);
                    $nextAge->state = AgePlayer::STATE_UNLOCKED;
                    $unlockedAgeIds[] = $nextAge->id;
                    $objectiveTreeChanged = true;

                    $firstPhaseOfNextAge = PhasePlayer::query()
                        ->where('player_id', $playerId)
                        ->where('age_player_id', $nextAge->id)
                        ->with(['phase'])
                        ->get()
                        ->filter(function ($phasePlayer) {
                            return $phasePlayer->phase !== null;
                        })
                        ->sortBy(function ($phasePlayer) {
                            return (int) $phasePlayer->phase->order;
                        })
                        ->first();

                    if (
                        $firstPhaseOfNextAge
                        && $firstPhaseOfNextAge->state === PhasePlayer::STATE_LOCKED
                    ) {
                        $firstPhaseOfNextAge->update([
                            'state' => PhasePlayer::STATE_UNLOCKED,
                        ]);
                        $unlockedPhaseIds[] = $firstPhaseOfNextAge->id;
                        $unlockFirstTargetForPhase((int) $firstPhaseOfNextAge->id);
                        $objectiveTreeChanged = true;
                    }
                }
            }

            $completedPhaseIds = PhasePlayer::query()
                ->where('player_id', $playerId)
                ->where('state', PhasePlayer::STATE_COMPLETED)
                ->pluck('id')
                ->all();

            $completedAgeIds = AgePlayer::query()
                ->where('player_id', $playerId)
                ->where('state', AgePlayer::STATE_COMPLETED)
                ->pluck('id')
                ->all();
        });

        $objectiveDrawCommands = [];
        $sessionId = $this->resolveSessionId($request, $player);
        $drawPlayerIdInput = $request->input('draw_player_id');
        $drawPlayerId = (is_numeric($drawPlayerIdInput) && (int) $drawPlayerIdInput > 0)
            ? (int) $drawPlayerIdInput
            : $playerId;
        $drawPlayer = Player::find($drawPlayerId) ?? $player;
        $objectiveTreeUidPrefix = 'objective_tree_' . $playerId;

        if (!empty($sessionId) && $objectiveTreeChanged) {
            ObjectCache::buffer($sessionId);

            $existingObjects = ObjectCache::all($sessionId);
            $objectiveRenderable = $this->resolveObjectiveRenderableFromCache($existingObjects, $objectiveTreeUidPrefix);
            $modalContext = $this->resolveObjectiveModalContext($existingObjects, $objectiveTreeUidPrefix);

            if ($modalContext !== null) {
                foreach ($modalContext['uids_to_clear'] as $uid) {
                    if (!isset($existingObjects[$uid])) {
                        continue;
                    }
                    $objectClear = new ObjectClear($uid, $sessionId);
                    $objectiveDrawCommands[] = $objectClear->get();
                    ObjectCache::forget($sessionId, $uid);
                }

                $objectiveTree = new ObjectiveTreeDraw($objectiveTreeUidPrefix, $player->fresh());
                $objectiveTree->setOrigin(0, 0);
                $objectiveTree->build();

                $modal = new ModalDraw($modalContext['modal_uid']);
                $modal->setOrigin($modalContext['x'], $modalContext['y']);
                $modal->setSize($modalContext['width'], $modalContext['height']);
                $modal->setTitle($modalContext['title']);
                $modal->setRenderable($modalContext['renderable']);

                foreach ($objectiveTree->getDrawItems() as $drawItem) {
                    $json = $drawItem->buildJson();
                    $offsetX = isset($json['x']) ? (int) $json['x'] : 0;
                    $offsetY = isset($json['y']) ? (int) $json['y'] : 0;
                    $modal->addContentItem($drawItem, $offsetX, $offsetY);
                }

                $modal->build();

                foreach ($modal->getDrawItems() as $drawItem) {
                    $objectDraw = new ObjectDraw($drawItem->buildJson(), $sessionId);
                    $objectiveDrawCommands[] = $objectDraw->get();
                }
            } else {
                foreach ($existingObjects as $uid => $object) {
                    if (\Illuminate\Support\Str::startsWith($uid, $objectiveTreeUidPrefix)) {
                        $objectClear = new ObjectClear($uid, $sessionId);
                        $objectiveDrawCommands[] = $objectClear->get();
                        ObjectCache::forget($sessionId, $uid);
                    }
                }

                $objectiveTree = new ObjectiveTreeDraw($objectiveTreeUidPrefix, $player->fresh());
                $objectiveTree->setOrigin(20, 20);
                $objectiveTree->setRenderable($objectiveRenderable);
                $objectiveTree->build();

                foreach ($objectiveTree->getDrawItems() as $drawItem) {
                    $objectDraw = new ObjectDraw($drawItem->buildJson(), $sessionId);
                    $objectiveDrawCommands[] = $objectDraw->get();
                }
            }

            if (!empty($updatedScores)) {
                $objectiveDrawCommands = array_merge(
                    $objectiveDrawCommands,
                    $this->buildScoreDrawUpdateCommands($playerId, $updatedScores, $sessionId)
                );
            }

            ObjectCache::flush($sessionId);

            if (!empty($objectiveDrawCommands)) {
                $objectiveRequestId = Str::random(20);
                DrawRequest::query()->create([
                    'session_id' => $sessionId,
                    'request_id' => $objectiveRequestId,
                    'player_id' => $drawPlayerId,
                    'items' => json_encode($objectiveDrawCommands),
                ]);

                foreach (array_values(array_unique($completedTargetIds)) as $completedTargetId) {
                    ExecuteCompletedTargetRewardScriptJob::dispatch(
                        (int) $completedTargetId,
                        (int) $player->id
                    );
                }
            }
        }
    }

    private function resolveObjectiveRenderableFromCache(array $existingObjects, string $objectiveTreeUidPrefix): bool
    {
        foreach ($existingObjects as $object) {
            $attributes = $object['attributes'] ?? null;
            if (!is_array($attributes)) {
                continue;
            }

            $scrollChildUids = $attributes['scroll_child_uids'] ?? null;
            if (!is_array($scrollChildUids) || empty($scrollChildUids)) {
                continue;
            }

            foreach ($scrollChildUids as $childUid) {
                if (is_string($childUid) && Str::startsWith($childUid, $objectiveTreeUidPrefix)) {
                    return (bool) ($attributes['renderable'] ?? true);
                }
            }
        }

        foreach ($existingObjects as $uid => $object) {
            if (is_string($uid) && Str::startsWith($uid, $objectiveTreeUidPrefix)) {
                return (bool) (($object['attributes']['renderable'] ?? true));
            }
        }

        return true;
    }

    private function resolveObjectiveModalContext(array $existingObjects, string $objectiveTreeUidPrefix): ?array
    {
        foreach ($existingObjects as $uid => $object) {
            $attributes = $object['attributes'] ?? null;
            if (!is_array($attributes)) {
                continue;
            }

            $scrollChildUids = $attributes['scroll_child_uids'] ?? null;
            if (!is_array($scrollChildUids) || empty($scrollChildUids)) {
                continue;
            }

            $containsObjective = false;
            foreach ($scrollChildUids as $childUid) {
                if (is_string($childUid) && Str::startsWith($childUid, $objectiveTreeUidPrefix)) {
                    $containsObjective = true;
                    break;
                }
            }
            if (!$containsObjective) {
                continue;
            }

            $modalUid = $attributes['modal_uid'] ?? null;
            if (!is_string($modalUid) || $modalUid === '') {
                if (is_string($uid) && Str::endsWith($uid, '_content_viewport')) {
                    $modalUid = substr($uid, 0, -strlen('_content_viewport'));
                } else {
                    continue;
                }
            }

            $bodyUid = $modalUid . '_body';
            $titleUid = $modalUid . '_title';
            $body = $existingObjects[$bodyUid] ?? null;
            $title = $existingObjects[$titleUid] ?? null;

            $x = isset($body['x']) ? (int) $body['x'] : 0;
            $y = isset($body['y']) ? (int) $body['y'] : 0;
            $width = isset($body['width']) ? (int) $body['width'] : 760;
            $height = isset($body['height']) ? (int) $body['height'] : 560;
            $renderable = (bool) (($body['attributes']['renderable'] ?? ($attributes['renderable'] ?? true)));
            $titleText = is_array($title) && isset($title['text']) ? (string) $title['text'] : 'Obiettivi';

            $uidsToClear = [];
            foreach ($existingObjects as $existingUid => $_) {
                if (is_string($existingUid) && Str::startsWith($existingUid, $modalUid . '_')) {
                    $uidsToClear[] = $existingUid;
                }
            }
            foreach ($scrollChildUids as $childUid) {
                if (is_string($childUid)) {
                    $uidsToClear[] = $childUid;
                }
            }

            return [
                'modal_uid' => $modalUid,
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height,
                'title' => $titleText,
                'renderable' => $renderable,
                'uids_to_clear' => array_values(array_unique($uidsToClear)),
            ];
        }

        return null;
    }

    private function buildScoreDrawUpdateCommands(int $playerId, array $updatedScores, string $sessionId): array
    {
        $commands = [];
        foreach ($updatedScores as $scoreId => $newValue) {
            $scoreDrawUid = 'player_' . $playerId . '_score_' . (int) $scoreId;
            try {
                $scoreDraw = new ScoreDraw($scoreDrawUid);
                $updates = $scoreDraw->updateValue((string) $newValue, $sessionId);
                foreach ($updates as $update) {
                    $commands[] = $update;
                }
            } catch (\Throwable $e) {
                Log::warning('ScoreDraw update skipped after objective refresh', [
                    'player_id' => $playerId,
                    'score_id' => (int) $scoreId,
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return $commands;
    }

    private function resolveSessionId(Request $request, Player $player): string
    {
        $requested = $request->input('session_id');
        if (is_string($requested) && trim($requested) !== '') {
            return $requested;
        }
        if (!empty($player->actual_session_id)) {
            return (string) $player->actual_session_id;
        }
        return 'init_session_id';
    }
}
