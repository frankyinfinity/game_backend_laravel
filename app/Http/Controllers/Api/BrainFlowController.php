<?php

namespace App\Http\Controllers\Api;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\BrainSchedulePath;
use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionNeuron;
use App\Models\Entity;
use App\Models\Player;
use App\Services\BrainScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
