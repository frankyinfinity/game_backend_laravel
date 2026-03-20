<?php

namespace App\Services;

use App\Custom\Draw\Complex\ModalDraw;
use App\Custom\Draw\Complex\Objective\ObjectiveTreeDraw;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Jobs\CheckObjectiveJob;
use App\Models\DrawRequest;
use App\Models\Player;
use App\Models\TargetPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ObjectiveService
{
    public function startObjective(Request $request): array
    {
        $playerId = (int)$request->input('player_id');
        $targetPlayerId = (int)$request->input('target_player_id');

        $alreadyInProgress = TargetPlayer::query()
            ->where('player_id', $playerId)
            ->where('state', TargetPlayer::STATE_IN_PROGRESS)
            ->where('id', '!=', $targetPlayerId)
            ->exists();

        if ($alreadyInProgress) {
            return [
                'status' => 422,
                'body' => [
                    'success' => false,
                    'message' => 'Puoi avviare un solo obiettivo alla volta',
                ],
            ];
        }

        $targetPlayer = TargetPlayer::query()
            ->where('id', $targetPlayerId)
            ->where('player_id', $playerId)
            ->first();

        if (!$targetPlayer) {
            return [
                'status' => 404,
                'body' => [
                    'success' => false,
                    'message' => 'Obiettivo non trovato',
                ],
            ];
        }

        if ($targetPlayer->state !== TargetPlayer::STATE_UNLOCKED) {
            return [
                'status' => 422,
                'body' => [
                    'success' => false,
                    'message' => 'Obiettivo non in stato unlocked',
                ],
            ];
        }

        $targetPlayer->update([
            'state' => TargetPlayer::STATE_IN_PROGRESS,
        ]);

        $player = Player::query()->find($playerId);
        $objectiveRequestId = null;

        if ($player) {
            $sessionId = $this->resolveSessionId($request, $player);
            $drawPlayerId = (int)$request->input('draw_player_id', $playerId);
            $drawPlayer = Player::query()->find($drawPlayerId) ?? $player;
            $objectiveTreeUidPrefix = 'objective_tree_' . $playerId;
            $objectiveDrawCommands = [];

            if ($sessionId !== '') {
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
                        $offsetX = isset($json['x']) ? (int)$json['x'] : 0;
                        $offsetY = isset($json['y']) ? (int)$json['y'] : 0;
                        $modal->addContentItem($drawItem, $offsetX, $offsetY);
                    }

                    $modal->build();

                    foreach ($modal->getDrawItems() as $drawItem) {
                        $objectDraw = new ObjectDraw($drawItem->buildJson(), $sessionId);
                        $objectiveDrawCommands[] = $objectDraw->get();
                    }
                }
                else {
                    foreach ($existingObjects as $uid => $object) {
                        if (!is_string($uid) || !Str::startsWith($uid, $objectiveTreeUidPrefix)) {
                            continue;
                        }

                        $objectClear = new ObjectClear($uid, $sessionId);
                        $objectiveDrawCommands[] = $objectClear->get();
                        ObjectCache::forget($sessionId, $uid);
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

                ObjectCache::flush($sessionId);

                if (!empty($objectiveDrawCommands)) {
                    $objectiveRequestId = Str::random(20);
                    DrawRequest::query()->create([
                        'session_id' => $sessionId,
                        'request_id' => $objectiveRequestId,
                        'player_id' => (int)$drawPlayer->id,
                        'items' => json_encode($objectiveDrawCommands),
                    ]);
                }
            }
        }

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Obiettivo avviato',
                'target_player_id' => $targetPlayer->id,
                'state' => $targetPlayer->state,
                'objective_redraw_request_id' => $objectiveRequestId,
            ],
        ];
    }

    public function setObjectiveModalVisibility(Request $request): array
    {
        $playerId = (int)$request->input('player_id');
        $player = Player::query()->find($playerId);

        if (!$player) {
            return [
                'status' => 404,
                'body' => [
                    'success' => false,
                    'message' => 'Player non trovato',
                ],
            ];
        }

        $sessionId = $this->resolveSessionId($request, $player);
        $modalUid = (string)$request->input('modal_uid', 'objective_modal_' . $playerId);
        $renderable = filter_var($request->input('renderable', true), FILTER_VALIDATE_BOOL);

        ObjectCache::buffer($sessionId);
        $existingObjects = ObjectCache::all($sessionId);

        $viewportUid = $modalUid . '_content_viewport';
        $childUids = [];
        if (
        isset($existingObjects[$viewportUid]['attributes']['scroll_child_uids'])
        && is_array($existingObjects[$viewportUid]['attributes']['scroll_child_uids'])
        ) {
            $childUids = $existingObjects[$viewportUid]['attributes']['scroll_child_uids'];
        }

        foreach ($existingObjects as $uid => $object) {
            if (!is_string($uid) || !Str::startsWith($uid, $modalUid . '_')) {
                continue;
            }

            if (!isset($object['attributes']) || !is_array($object['attributes'])) {
                $object['attributes'] = [];
            }

            $object['attributes']['renderable'] = $renderable;
            ObjectCache::put($sessionId, $object);
        }

        foreach ($childUids as $childUid) {
            if (!isset($existingObjects[$childUid])) {
                continue;
            }

            $object = $existingObjects[$childUid];
            if (!isset($object['attributes']) || !is_array($object['attributes'])) {
                $object['attributes'] = [];
            }

            $object['attributes']['renderable'] = $renderable;
            ObjectCache::put($sessionId, $object);
        }

        ObjectCache::flush($sessionId);

        return [
            'status' => 200,
            'body' => ['success' => true],
        ];
    }

    public function dispatchObjectiveCheck(Request $request): array
    {
        $playerId = (int)$request->input('player_id');
        $drawPlayerIdInput = $request->input('draw_player_id');
        $drawPlayerId = (is_numeric($drawPlayerIdInput) && (int)$drawPlayerIdInput > 0)
            ? (int)$drawPlayerIdInput
            : $playerId;

        CheckObjectiveJob::dispatch([
            'player_id' => $playerId,
            'session_id' => $request->input('session_id'),
            'draw_player_id' => $drawPlayerId,
        ]);

        return [
            'status' => 200,
            'body' => ['success' => true],
        ];
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
                    return (bool)($attributes['renderable'] ?? true);
                }
            }
        }

        foreach ($existingObjects as $uid => $object) {
            if (is_string($uid) && Str::startsWith($uid, $objectiveTreeUidPrefix)) {
                return (bool)($object['attributes']['renderable'] ?? true);
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
                }
                else {
                    continue;
                }
            }

            $bodyUid = $modalUid . '_body';
            $titleUid = $modalUid . '_title';
            $body = $existingObjects[$bodyUid] ?? null;
            $title = $existingObjects[$titleUid] ?? null;

            $x = isset($body['x']) ? (int)$body['x'] : 0;
            $y = isset($body['y']) ? (int)$body['y'] : 0;
            $width = isset($body['width']) ? (int)$body['width'] : 760;
            $height = isset($body['height']) ? (int)$body['height'] : 560;
            $renderable = (bool)($body['attributes']['renderable'] ?? $attributes['renderable'] ?? true);
            $titleText = is_array($title) && isset($title['text']) ? (string)$title['text'] : 'Obiettivi';

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

    private function resolveSessionId(Request $request, Player $player): string
    {
        $requested = $request->input('session_id');
        if (is_string($requested) && trim($requested) !== '') {
            return $requested;
        }

        if (!empty($player->actual_session_id)) {
            return (string)$player->actual_session_id;
        }

        return 'init_session_id';
    }
}
