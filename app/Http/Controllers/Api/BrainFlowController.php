<?php

namespace App\Http\Controllers\Api;

use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\BrainSchedulePath;
use App\Models\Container;
use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionInformation;
use App\Models\ElementHasPositionNeuron;
use App\Models\Entity;
use App\Models\EntityInformation;
use App\Models\Gene;
use App\Models\Genome;
use App\Models\Player;
use App\Services\BrainScheduleService;
use App\Services\DockerContainerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BrainFlowController extends Controller
{
    /**
     * Porta lo BrainSchedule indicato allo stato finale.
     * Invocato dal container element (comando websocket 'execute-brain').
     */
    public function finish(Request $request, BrainScheduleService $brainScheduleService): JsonResponse
    {
        ini_set('memory_limit', '-1');
        $validated = $request->validate([
            'brain_schedule_id' => ['required', 'integer'],
        ]);

        $result = $brainScheduleService->finishById((int) $validated['brain_schedule_id']);
        return response()->json($result['body'], $result['status']);
    }

    /**
     * Ritorna tutti i percorsi possibili enumerati per lo BrainSchedule
     * (path + step ordinati per seq), popolati da BrainPathService nel job.
     */
    public function paths(Request $request): JsonResponse
    {
        ini_set('memory_limit', '-1');
        $validated = $request->validate([
            'brain_schedule_id' => ['required', 'integer'],
        ]);

        $paths = BrainSchedulePath::query()
            ->where('brain_schedule_id', (int) $validated['brain_schedule_id'])
            ->with([
                'steps' => fn($q) => $q->orderBy('seq'),
                'steps.neuron.chemicalRule',
                'steps.neuron.positionInformation',
                'steps.neuron.outgoingLinks.conditionOrder.ruleChimicalElementDetail',
                'steps.viaLink.conditionOrder.ruleChimicalElementDetail',
            ])
            ->get();

        // Path raggruppati per circuito: il container itera prima i circuiti,
        // poi segue dinamicamente gli step di ogni path.
        $circuits = $paths
            ->groupBy('element_has_position_neuron_circuit_id')
            ->map(fn($group, $circuitId) => [
                'element_has_position_neuron_circuit_id' => (int) $circuitId,
                'paths' => $group->values(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'brain_schedule_id' => (int) $validated['brain_schedule_id'],
            'circuits' => $circuits,
        ]);
    }

    /**
     * Aggiorna la posizione persistente (tile_i, tile_j) dell'elemento dopo il
     * movimento: stesso ruolo di entity update_position (una chiamata a fine
     * movimento con la posizione finale).
     */
    public function updatePosition(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'element_has_position_id' => ['required', 'integer'],
            'tile_i' => ['required', 'integer'],
            'tile_j' => ['required', 'integer'],
        ]);

        $elementHasPosition = ElementHasPosition::query()->find((int) $validated['element_has_position_id']);
        if (!$elementHasPosition) {
            return response()->json(['success' => false, 'message' => 'element_has_position not found'], 404);
        }

        $elementHasPosition->update([
            'tile_i' => (int) $validated['tile_i'],
            'tile_j' => (int) $validated['tile_j'],
        ]);

        return response()->json([
            'success' => true,
            'tile_i' => (int) $elementHasPosition->tile_i,
            'tile_j' => (int) $elementHasPosition->tile_j,
        ]);
    }

    /**
     * Marca come usato (used=true) il BrainSchedulePath effettivamente
     * eseguito: il container element lo chiama quando attraversa un neurone END.
     */
    public function pathUsed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brain_schedule_path_id' => ['required', 'integer'],
        ]);

        $path = BrainSchedulePath::query()->find((int) $validated['brain_schedule_path_id']);
        if ($path === null) {
            return response()->json(['success' => false, 'message' => 'BrainSchedulePath not found'], 404);
        }

        $path->update(['used' => true]);

        return response()->json([
            'success' => true,
            'brain_schedule_path_id' => (int) $path->id,
            'used' => true,
        ]);
    }

    /**
     * Attiva/disattiva un neurone e invia via pusher l'update del suo border
     * (stesso payload di NeuronController::broadcastNeuronUpdate).
     */
    public function neuronState(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'element_has_position_neuron_id' => ['required', 'integer'],
            'active' => ['required', 'boolean'],
        ]);

        $neuron = ElementHasPositionNeuron::query()->find((int) $validated['element_has_position_neuron_id']);
        if ($neuron === null) {
            return response()->json(['success' => false, 'message' => 'Neuron not found'], 404);
        }

        $active = (bool) $validated['active'];
        $neuron->update(['active' => $active]);

        return response()->json([
            'success' => true,
            'neuron_id' => $neuron->id,
            'active' => $active,
        ]);
    }

    /**
     * L'elemento attacca l'entity presente su una tile adiacente (port del
     * ramo entity di performAttackForElement del runner). Danno dal gene
     * attack dell'attaccante, vita entity da Genome/EntityInformation.
     * Se uccisa: state DEATH + stop container + items di clear per
     * l'interfaccia (inviati via pusher dal container element).
     */
    public function attack(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'element_has_position_id' => ['required', 'integer'],
            'tile_i' => ['required', 'integer'],
            'tile_j' => ['required', 'integer'],
            'gene_attack_id' => ['nullable', 'integer'],
            'gene_life_id' => ['nullable', 'integer'],
        ]);

        $attacker = ElementHasPosition::query()
            ->where('id', (int) $validated['element_has_position_id'])
            ->where('state', ElementHasPosition::STATE_LIFE)
            ->first();
        if ($attacker === null) {
            return response()->json(['success' => false, 'message' => 'Attacker ElementHasPosition non trovato'], 404);
        }

        $targetEntity = Entity::query()
            ->where('state', Entity::STATE_LIFE)
            ->where('tile_i', (int) $validated['tile_i'])
            ->where('tile_j', (int) $validated['tile_j'])
            ->first();
        if ($targetEntity === null) {
            return response()->json(['success' => false, 'message' => 'Nessuna entity viva sulla tile'], 404);
        }

        $attackGeneId = (int) ($validated['gene_attack_id'] ?? 0);
        $lifeGeneId = (int) ($validated['gene_life_id'] ?? 0);
        if ($attackGeneId <= 0) {
            $attackGeneId = (int) (Gene::query()->where('key', Gene::KEY_ATTACK)->value('id') ?? 0);
        }
        if ($lifeGeneId <= 0) {
            $lifeGeneId = (int) (Gene::query()->where('key', Gene::KEY_LIFEPOINT)->value('id') ?? 0);
        }

        // Danno dal gene attack dell'attaccante (ElementHasPositionInformation).
        $attackInfo = ElementHasPositionInformation::query()
            ->where('element_has_position_id', (int) $attacker->id)
            ->where('gene_id', $attackGeneId)
            ->first();
        $damage = (int) ($attackInfo->value ?? 0);
        if ($damage <= 0) {
            return response()->json([
                'success' => true,
                'attacked' => false,
                'damage' => 0,
                'entity_killed' => false,
                'entity_uid' => (string) $targetEntity->uid,
                'items' => [],
            ]);
        }

        $lifeGenome = Genome::query()
            ->where('entity_id', (int) $targetEntity->id)
            ->where('gene_id', $lifeGeneId)
            ->first();
        $lifeInfo = $lifeGenome !== null
            ? EntityInformation::query()->where('genome_id', (int) $lifeGenome->id)->first()
            : null;
        if ($lifeInfo === null) {
            return response()->json(['success' => false, 'message' => 'Entity senza informazione vita'], 422);
        }

        $newLife = ((int) $lifeInfo->value) - $damage;
        $lifeInfo->update(['value' => $newLife]);

        $player = Player::query()->find((int) $attacker->player_id);
        $sessionId = $player !== null ? (string) ($player->actual_session_id ?? '') : '';

        $entityKilled = false;
        $items = [];
        $targetUid = (string) $targetEntity->uid;

        if ($newLife <= 0) {
            $entityKilled = true;
            $targetEntity->update(['state' => Entity::STATE_DEATH]);

            if ($sessionId !== '') {
                $fallbackUids = $this->buildEntityClearFallbackUids($targetEntity, $targetUid);
                $idsToClear = $this->resolveDrawUidsForObject($sessionId, $targetUid, $fallbackUids);
                foreach ($idsToClear as $uid) {
                    $items[] = (new ObjectClear($uid, $sessionId))->get();
                    ObjectCache::forget($sessionId, $uid);
                }
            }

            $this->stopContainerByParent(Container::PARENT_TYPE_ENTITY, (int) $targetEntity->id);
        }

        return response()->json([
            'success' => true,
            'attacked' => true,
            'damage' => $damage,
            'new_health' => $newLife,
            'entity_killed' => $entityKilled,
            'entity_uid' => $targetUid,
            'entity_tile_i' => (int) $targetEntity->tile_i,
            'entity_tile_j' => (int) $targetEntity->tile_j,
            'items' => $items,
        ]);
    }

    /**
     * Celle valide per il neurone TYPE_MOVEMENT: entro `radius`, liquide,
     * non solide, non occupate. La scelta casuale è lato container (brain.js).
     */
    public function moveCandidates(Request $request): JsonResponse
    {
        ini_set('memory_limit', '-1');
        $validated = $request->validate([
            'element_has_position_neuron_id' => ['required', 'integer'],
        ]);

        [$neuron, $elementHasPosition, $error] = $this->resolveMovementNeuron((int) $validated['element_has_position_neuron_id']);
        if ($error !== null) {
            return $error;
        }

        $candidates = $this->movementCandidates($elementHasPosition, (int) $neuron->radius);
        if ($candidates === null) {
            return response()->json(['success' => false, 'message' => 'Player/birthRegion non trovati'], 404);
        }

        return response()->json([
            'success' => true,
            'neuron_id' => $neuron->id,
            'from' => [
                'i' => (int) $elementHasPosition->tile_i,
                'j' => (int) $elementHasPosition->tile_j,
            ],
            'candidates' => $candidates,
        ]);
    }

    /**
     * Risolve neurone + elementHasPosition per gli endpoint di movimento.
     *
     * @return array{0:?ElementHasPositionNeuron, 1:?ElementHasPosition, 2:?JsonResponse}
     */
    private function resolveMovementNeuron(int $neuronId): array
    {
        $neuron = ElementHasPositionNeuron::query()->find($neuronId);
        if ($neuron === null) {
            return [null, null, response()->json(['success' => false, 'message' => 'Neuron not found'], 404)];
        }

        $elementHasPosition = $neuron->brain?->elementHasPosition;
        if ($elementHasPosition === null) {
            return [null, null, response()->json(['success' => false, 'message' => 'ElementHasPosition non trovato'], 404)];
        }

        $range = (int) ($neuron->radius ?? 0);
        if ($range <= 0) {
            return [null, null, response()->json(['success' => false, 'message' => 'Radius non valido'], 422)];
        }

        return [$neuron, $elementHasPosition, null];
    }

    /**
     * Celle raggiungibili entro `range`: bounds, non solide, liquide,
     * senza entità vive né altri elementi. Null se player/region mancanti.
     */
    private function movementCandidates(ElementHasPosition $elementHasPosition, int $range): ?array
    {
        $player = Player::query()->with('birthRegion')->find((int) $elementHasPosition->player_id);
        if ($player === null || $player->birthRegion === null) {
            return null;
        }

        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
        $solidMap = Helper::getMapSolidTiles($tiles, $birthRegion);

        $centerI = (int) $elementHasPosition->tile_i;
        $centerJ = (int) $elementHasPosition->tile_j;
        $candidates = [];

        for ($i = $centerI - $range; $i <= $centerI + $range; $i++) {
            for ($j = $centerJ - $range; $j <= $centerJ + $range; $j++) {
                if ($i === $centerI && $j === $centerJ) {
                    continue;
                }
                if ($i < 0 || $j < 0 || $i >= (int) $birthRegion->height || $j >= (int) $birthRegion->width) {
                    continue;
                }
                if (($solidMap[$i][$j] ?? 'X') !== '0') {
                    continue;
                }
                if (!$this->isLiquidTileAt($tiles, $i, $j)) {
                    continue;
                }

                $hasLiveEntity = Entity::query()
                    ->where('state', Entity::STATE_LIFE)
                    ->where('tile_i', $i)
                    ->where('tile_j', $j)
                    ->exists();
                if ($hasLiveEntity) {
                    continue;
                }

                $hasElement = ElementHasPosition::query()
                    ->where('id', '!=', (int) $elementHasPosition->id)
                    ->where('tile_i', $i)
                    ->where('tile_j', $j)
                    ->exists();
                if ($hasElement) {
                    continue;
                }

                $candidates[] = ['i' => $i, 'j' => $j];
            }
        }

        return $candidates;
    }

    private function isLiquidTileAt($tiles, int $tileI, int $tileJ): bool
    {
        if ($tiles instanceof \Illuminate\Support\Collection) {
            $tile = $tiles->where('i', $tileI)->where('j', $tileJ)->first();
            return is_array($tile)
                && isset($tile['tile']['type'])
                && $tile['tile']['type'] === 1;
        }

        return false;
    }

    /** UID di fallback per il clear draw di una entity (stesso set del runner). */
    private function buildEntityClearFallbackUids(Entity $targetEntity, string $targetUid): array
    {
        $fallback = [
            $targetUid,
            $targetUid . '_panel',
            $targetUid . '_text_row_1',
            $targetUid . '_text_row_2',
        ];

        $buttonBases = [
            $targetUid . '_button_up',
            $targetUid . '_button_left',
            $targetUid . '_button_down',
            $targetUid . '_button_right',
            $targetUid . '_button_division',
        ];
        foreach ($buttonBases as $base) {
            $fallback[] = $base;
            $fallback[] = $base . '_rect';
            $fallback[] = $base . '_text';
        }

        $genomes = Genome::query()
            ->where('entity_id', (int) $targetEntity->id)
            ->with(['gene'])
            ->get();
        foreach ($genomes as $genome) {
            $gene = $genome->gene;
            if ($gene === null || ($gene->type ?? null) !== Gene::DYNAMIC_MAX) {
                continue;
            }
            $progressUid = $targetUid . '_progress_bar_' . $gene->key;
            $fallback[] = $progressUid;
            $fallback[] = $progressUid . '_border';
            $fallback[] = $progressUid . '_bar';
            $fallback[] = $progressUid . '_text';
            $fallback[] = $progressUid . '_range';
        }

        return array_values(array_unique($fallback));
    }

    /** Risolve gli uid draw reali dall'ObjectCache, con fallback sui suffix noti. */
    private function resolveDrawUidsForObject(string $sessionId, string $rootUid, array $fallbackUids = []): array
    {
        $uids = [];
        $rootObject = ObjectCache::find($sessionId, $rootUid);
        if (is_array($rootObject)) {
            $attributes = $rootObject['attributes'] ?? null;
            $cachedUids = is_array($attributes) ? ($attributes['uids'] ?? null) : null;
            if (is_array($cachedUids)) {
                foreach ($cachedUids as $uid) {
                    if (is_string($uid) && $uid !== '') {
                        $uids[] = $uid;
                    }
                }
            }
        }

        if (empty($uids)) {
            foreach ($fallbackUids as $uid) {
                if (is_string($uid) && $uid !== '') {
                    $uids[] = $uid;
                }
            }
        }

        if ($rootUid !== '') {
            $uids[] = $rootUid;
        }

        return array_values(array_unique($uids));
    }

    /** Ferma e rimuove il container docker associato a parent_type/parent_id. */
    private function stopContainerByParent(string $parentType, int $parentId): void
    {
        $container = Container::query()
            ->where('parent_type', $parentType)
            ->where('parent_id', $parentId)
            ->orderByDesc('id')
            ->first();
        if ($container === null) {
            return;
        }

        try {
            try {
                app(DockerContainerService::class)->stopContainer($container);
            } catch (\Throwable $e) {
                Log::warning('Unable to stop container', [
                    'parent_type' => $parentType,
                    'parent_id' => $parentId,
                    'container_id' => $container->container_id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                app(DockerContainerService::class)->deleteContainer($container, true);
            } catch (\Throwable $e) {
                Log::warning('Unable to delete container', [
                    'parent_type' => $parentType,
                    'parent_id' => $parentId,
                    'container_id' => $container->container_id,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('stopContainerByParent failed', [
                'parent_type' => $parentType,
                'parent_id' => $parentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
