<?php

namespace App\Services;

use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionNeuron;
use App\Models\Container;
use App\Models\DrawRequest;
use App\Models\Entity;
use App\Models\EntityInformation;
use App\Models\ElementHasPositionInformation;
use App\Models\Gene;
use App\Models\BrainSchedule;
use App\Models\BrainScheduleDetail;
use App\Models\Neuron;
use App\Models\NeuronLink;
use App\Models\Tile;
use App\Models\Player;
use App\Models\Genome;
use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Draw\Complex\ProgressBarDraw;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectCode;
use App\Custom\Manipulation\ObjectDraw;
use App\Custom\Manipulation\ObjectUpdate;
use App\Custom\Draw\Support\ScrollGroup;
use App\Helper\Helper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BrainFlowRunner
{
    private array $processedNeuronsById = [];
    private array $queuedDrawBySession = [];
    private int $elementHasPositionId = 0;
    private string $wsHost = '';
    private int $websocketGatewayPort = 9001;

    public function run(int $elementHasPositionId, string $wsHost = ''): array
    {
        $this->elementHasPositionId = $elementHasPositionId;
        $this->wsHost = $wsHost !== '' ? $wsHost : (string) config('remote_docker.docker_host_ip');
        $this->websocketGatewayPort = (int) config('remote_docker.websocket_gateway_port', 9001);

        $item = ElementHasPosition::query()
            ->with(['brain'])
            ->find($elementHasPositionId);

        if ($item === null) {
            throw new \RuntimeException("ElementHasPosition {$elementHasPositionId} non trovato.");
        }

        $brainSchedule = BrainSchedule::where('element_has_position_id', $elementHasPositionId)
            ->where('state', BrainSchedule::STATE_IN_PROGRESS)
            ->with(['details.elementHasPositionNeuronCircuit.details'])
            ->first();

        if (!$brainSchedule) {
            return [];
        }

        $this->processedNeuronsById = [];
        $this->queuedDrawBySession = [];
        $terminalReached = false;
        $processedFlow = [];

        foreach ($brainSchedule->details as $detail) {
            $circuit = $detail->elementHasPositionNeuronCircuit;
            if (!$circuit) {
                continue;
            }

            $neuronIds = $circuit->details->pluck('element_has_position_neuron_id');
            $neurons = ElementHasPositionNeuron::query()
                ->whereIn('id', $neuronIds)
                ->with(['outgoingLinks', 'incomingLinks'])
                ->get()
                ->values();

            if ($neurons->isEmpty()) {
                continue;
            }

            $neuronsById = $neurons->keyBy('id');
            $incomingCount = [];
            $incomingById = [];
            $outgoingById = [];

            foreach ($neurons as $neuron) {
                $incomingById[$neuron->id] = $neuron->incomingLinks
                    ->filter(fn($link) => $neuronIds->contains($link->from_element_has_position_neuron_id))
                    ->sortBy('id')->values();

                $outgoingById[$neuron->id] = $neuron->outgoingLinks
                    ->filter(fn($link) => $neuronIds->contains($link->to_element_has_position_neuron_id))
                    ->sortBy('id')->values();

                $incomingCount[$neuron->id] = (int) $incomingById[$neuron->id]->count();
            }

            $queue = [];
            $startNeuronId = (int) $circuit->start_element_has_position_neuron_id;

            if ($startNeuronId > 0 && $neuronsById->has($startNeuronId)) {
                $queue[] = [
                    'id' => $startNeuronId,
                    'from' => null,
                ];
            } else {
                foreach ($neurons as $neuron) {
                    if (($incomingCount[$neuron->id] ?? 0) === 0) {
                        $queue[] = [
                            'id' => (int) $neuron->id,
                            'from' => null,
                        ];
                    }
                }
            }

            $seen = [];
            $terminalNeuronIdInCircuit = null;

            while (!empty($queue)) {
                $entry = array_shift($queue);
                $currentId = (int) ($entry['id'] ?? 0);
                if ($currentId <= 0 || isset($seen[$currentId])) {
                    continue;
                }

                $model = $neuronsById->get($currentId);
                if ($model === null) {
                    continue;
                }

                $neuronData = $model->attributesToArray();
                $neuronData['neuron_from'] = $entry['from'];

                $this->setNeuronActiveState($model, true);

                try {
                    $this->handleNeuronByType($neuronData, $item);
                    $this->processedNeuronsById[$currentId] = $neuronData;
                    $processedFlow[] = $neuronData;
                    $seen[$currentId] = true;

                    $hasActiveOutgoing = false;
                    $outgoingLinks = $outgoingById[$currentId] ?? [];
                    $neuronType = $neuronData['type'] ?? null;

                    if ($neuronType === Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
                        $hasActiveOutgoing = $this->processChemicalElementOutgoingLinks($neuronData, $outgoingLinks, $currentId, $queue, $seen);
                    } else {
                        foreach ($outgoingLinks as $link) {
                            if (!$this->isLinkActiveFromNeuron($neuronData, $link)) {
                                continue;
                            }

                            $hasActiveOutgoing = true;
                            $toId = (int) $link->to_element_has_position_neuron_id;
                            if ($toId > 0 && !isset($seen[$toId])) {
                                $queue[] = [
                                    'id' => $toId,
                                    'from' => [
                                        'id' => (int) $currentId,
                                        'type' => $neuronData['type'] ?? null,
                                        'condition' => $link->condition ?? null,
                                    ],
                                ];
                            }
                        }
                    }

                    if (($neuronData['is_active'] ?? false) === true && !$hasActiveOutgoing) {
                        $terminalReached = true;
                        $terminalNeuronIdInCircuit = $currentId;
                    }
                } finally {
                    $this->setNeuronActiveState($model, false);
                }
            }

            if ($terminalNeuronIdInCircuit !== null) {
                $terminalModel = $neuronsById->get($terminalNeuronIdInCircuit);
                if ($terminalModel !== null) {
                    $terminalModel->refresh();
                    $this->setNeuronActiveState($terminalModel, false);
                }
            }
        }

        $this->queueCodeAtEndOfQueuedDraws($this->getEndOfTestBrainCode());
        $this->dispatchQueuedDrawRequests();

        if ($terminalReached) {
            $this->markBrainScheduleFinished($this->elementHasPositionId);
        }

        return $processedFlow;
    }

    private function buildOrderedNeuronsWithFromLink(ElementHasPosition $elementHasPosition): array
    {
        $brain = $elementHasPosition->brain;
        if ($brain === null) {
            return [];
        }

        $neurons = $brain->neurons->values();
        $neuronsById = $neurons->keyBy('id');

        $sortKey = function (ElementHasPositionNeuron $n): string {
            return sprintf('%05d_%05d_%010d', (int) $n->grid_i, (int) $n->grid_j, (int) $n->id);
        };

        $incomingById = [];
        $outgoingById = [];
        $indegree = [];

        foreach ($neurons as $current) {
            $incomingById[$current->id] = $current->incomingLinks->sortBy('id')->values();
            $outgoingById[$current->id] = $current->outgoingLinks->sortBy('id')->values();
            $indegree[$current->id] = (int) $incomingById[$current->id]->count();
        }

        $queue = $neurons
            ->filter(fn(ElementHasPositionNeuron $n) => ($indegree[$n->id] ?? 0) === 0)
            ->sortBy(fn(ElementHasPositionNeuron $n) => $sortKey($n))
            ->values()
            ->all();

        $ordered = [];
        while (!empty($queue)) {
            /** @var ElementHasPositionNeuron $node */
            $node = array_shift($queue);
            $ordered[] = $node;

            foreach ($outgoingById[$node->id] ?? [] as $link) {
                $toId = (int) $link->to_element_has_position_neuron_id;
                if (!isset($indegree[$toId])) {
                    continue;
                }
                $indegree[$toId]--;
                if ($indegree[$toId] === 0 && isset($neuronsById[$toId])) {
                    $queue[] = $neuronsById[$toId];
                    usort($queue, function (ElementHasPositionNeuron $a, ElementHasPositionNeuron $b) use ($sortKey): int {
                        return $sortKey($a) <=> $sortKey($b);
                    });
                }
            }
        }

        if (count($ordered) !== $neurons->count()) {
            $ordered = $neurons->sortBy(fn(ElementHasPositionNeuron $n) => $sortKey($n))->values()->all();
        }

        $result = [];
        foreach ($ordered as $current) {
            /** @var ElementHasPositionNeuron $current */
            $neuronFrom = null;
            foreach ($incomingById[$current->id] ?? [] as $link) {
                $fromNeuron = $neuronsById->get((int) $link->from_element_has_position_neuron_id);
                if ($fromNeuron !== null) {
                    $neuronFrom = [
                        'id' => (int) $fromNeuron->id,
                        'type' => $fromNeuron->type,
                        'condition' => $link->condition ?? null,
                    ];
                    break;
                }

                $neuronFrom = [
                    'id' => (int) $link->from_element_has_position_neuron_id,
                    'type' => null,
                    'condition' => $link->condition ?? null,
                ];
                break;
            }

            $result[] = array_merge(
                $current->attributesToArray(),
                ['neuron_from' => $neuronFrom]
            );
        }

        return $result;
    }

    private function handleNeuronByType(array &$neuron, $elementHasPosition): void
    {
        if (!$this->shouldProcessNeuronFromCondition($neuron)) {
            $neuron['is_active'] = false;
            return;
        }

        $neuron['is_active'] = true;
        switch ($neuron['type'] ?? null) {
            case Neuron::TYPE_START:
            case Neuron::TYPE_END:
                break;
            case Neuron::TYPE_DETECTION:
                $this->handleDetectionNeuron($neuron);
                break;
            case Neuron::TYPE_PATH:
                $this->handlePathNeuron($neuron, $elementHasPosition);
                break;
            case Neuron::TYPE_ATTACK:
                $this->handleAttackNeuron($neuron, $elementHasPosition);
                break;
            case Neuron::TYPE_MOVEMENT:
                $this->handleMovementNeuron($neuron, $elementHasPosition);
                break;
            case Neuron::TYPE_READ_CHIMICAL_ELEMENT:
                $this->handleReadChemicalElementNeuron($neuron, $elementHasPosition);
                break;
            default:
                $this->handleUnknownNeuron($neuron);
                break;
        }
    }

    private function setNeuronActiveState(ElementHasPositionNeuron $neuron, bool $active): void
    {
        if ((bool) $neuron->active === $active) {
            return;
        }

        $neuron->active = $active;
        $neuron->save();
    }

    private function handleMovementNeuron(array $neuron, $elementHasPosition): void
    {
        $range = (int) ($neuron['radius'] ?? 0);
        if ($range <= 0) {
            return;
        }

        $player = Player::query()->with('birthRegion')->find((int) $elementHasPosition->player_id);
        if ($player === null || $player->birthRegion === null) {
            return;
        }

        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
        $solidMap = Helper::getMapSolidTiles($tiles, $birthRegion);

        $centerI = (int) $elementHasPosition->tile_i;
        $centerJ = (int) $elementHasPosition->tile_j;
        $candidates = [];

        foreach ($this->buildRangeCoordinates($centerI, $centerJ, $range) as [$tileI, $tileJ]) {
            if ($tileI === $centerI && $tileJ === $centerJ) {
                continue;
            }
            if ($tileI < 0 || $tileJ < 0 || $tileI >= (int) $birthRegion->height || $tileJ >= (int) $birthRegion->width) {
                continue;
            }
            if (($solidMap[$tileI][$tileJ] ?? 'X') !== '0') {
                continue;
            }
            if (!$this->isLiquidTileAt($tiles, $tileI, $tileJ)) {
                continue;
            }

            $hasLiveEntity = Entity::query()
                ->where('state', Entity::STATE_LIFE)
                ->where('tile_i', $tileI)
                ->where('tile_j', $tileJ)
                ->exists();
            if ($hasLiveEntity) {
                continue;
            }

            $hasElement = ElementHasPosition::query()
                ->where('id', '!=', (int) $elementHasPosition->id)
                ->where('tile_i', $tileI)
                ->where('tile_j', $tileJ)
                ->exists();
            if ($hasElement) {
                continue;
            }

            $candidates[] = ['i' => $tileI, 'j' => $tileJ];
        }

        if (empty($candidates)) {
            return;
        }

        shuffle($candidates);
        $target = $candidates[0];
        $from = ['i' => $centerI, 'j' => $centerJ];

        $this->drawPathForPlayer((int) $elementHasPosition->player_id, $from, $target, $elementHasPosition, false);
    }

    private function handleReadChemicalElementNeuron(array &$neuron, ElementHasPosition $elementHasPosition): void
    {
        $ruleId = (int) ($neuron['element_has_rule_chimical_element_id'] ?? 0);
        if ($ruleId <= 0) {
            Log::info('ReadChemical: no rule_id');
            $neuron['chemical_element_value'] = null;
            return;
        }

        $container = $this->resolveElementContainer($elementHasPosition);
        if ($container === null || empty($container->ws_port)) {
            Log::info('ReadChemical: no container or ws_port');
            $neuron['chemical_element_value'] = null;
            return;
        }

        $host = (string) $this->wsHost;
        $port = (int) $container->ws_port;

        try {
            $socket = $this->openMapWebSocket($host, $port);
            try {
                $this->readWebSocketFrame($socket);

                $payload = [
                    'command' => 'get_chimical_elements',
                ];
                $this->writeWebSocketFrame($socket, json_encode($payload));

                $replyRaw = $this->readWebSocketFrame($socket);
                if ($replyRaw === null) {
                    Log::info('ReadChemical: no reply from websocket');
                    $neuron['chemical_element_value'] = null;
                    return;
                }

                Log::info('ReadChemical: raw websocket reply', ['raw' => $replyRaw]);

                $reply = json_decode($replyRaw, true);
                if (!is_array($reply)) {
                    Log::info('ReadChemical: invalid json', ['reply' => $replyRaw]);
                    $neuron['chemical_element_value'] = null;
                    return;
                }

                $chimicalElements = $reply['chimical_elements'] ?? [];
                if (!is_array($chimicalElements)) {
                    Log::info('ReadChemical: chimical_elements not array');
                    $neuron['chemical_element_value'] = null;
                    return;
                }

                $ruleId = (int) ($neuron['element_has_rule_chimical_element_id'] ?? 0);
                if ($ruleId <= 0) {
                    Log::info('ReadChemical: no rule_id');
                    $neuron['chemical_element_value'] = null;
                    return;
                }

                // Get the RuleChimicalElement to know which underlying element to look for
                $ruleElement = \App\Models\RuleChimicalElement::query()
                    ->where('id', $ruleId)
                    ->first();

                if ($ruleElement === null) {
                    Log::info('ReadChemical: rule element not found', ['ruleId' => $ruleId]);
                    $neuron['chemical_element_value'] = null;
                    return;
                }

                // Determine which ID to match based on which field is set in the rule
                $expectedId = null;
                $expectedType = null;

                if ($ruleElement->chimical_element_id !== null) {
                    $expectedId = (int) $ruleElement->chimical_element_id;
                    $expectedType = 'chimical_element';
                } elseif ($ruleElement->complex_chimical_element_id !== null) {
                    $expectedId = (int) $ruleElement->complex_chimical_element_id;
                    $expectedType = 'complex_chimical_element';
                }

                Log::info('ReadChemical: looking for underlying element', ['expectedId' => $expectedId, 'expectedType' => $expectedType]);

                $foundElement = null;
                foreach ($chimicalElements as $element) {
                    if (!is_array($element)) {
                        continue;
                    }
                    Log::info('ReadChemical: checking element', ['element' => $element]);

                    $elementType = $element['type'] ?? '';
                    $elementId = null;

                    if ($elementType === 'chimical_element') {
                        $elementId = (int) ($element['chimical_element_id'] ?? 0);
                    } elseif ($elementType === 'complex_chimical_element') {
                        $elementId = (int) ($element['complex_chimical_element_id'] ?? 0);
                    }

                    if ($elementId > 0 && $elementId === $expectedId) {
                        $foundElement = $element;
                        break;
                    }
                }

                if ($foundElement !== null) {
                    $neuron['chemical_element_value'] = (int) ($foundElement['value'] ?? 0);
                    Log::info('ReadChemical: value set', ['value' => $neuron['chemical_element_value']]);
                } else {
                    Log::info('ReadChemical: element not found in websocket response');
                    $neuron['chemical_element_value'] = null;
                }
            } finally {
                fclose($socket);
            }
        } catch (\Throwable $e) {
            Log::info('ReadChemical: exception', ['error' => $e->getMessage()]);
            $neuron['chemical_element_value'] = null;
        }
    }

    private function resolveElementContainer(ElementHasPosition $elementHasPosition): ?Container
    {
        return Container::query()
            ->where('parent_type', Container::PARENT_TYPE_ELEMENT_HAS_POSITION)
            ->where('parent_id', (int) $elementHasPosition->id)
            ->whereNotNull('ws_port')
            ->orderByDesc('id')
            ->first();
    }

    private function handleDetectionNeuron(array &$neuron): void
    {
        $elementHasPositionId = (int) $this->elementHasPositionId;
        $elementHasPosition = ElementHasPosition::query()->find($elementHasPositionId);
        if ($elementHasPosition === null) {
            $neuron['detection_result'] = null;
            return;
        }

        $range = (int) ($neuron['range'] ?? $neuron['radius'] ?? 0);
        if ($range < 0) {
            $range = 0;
        }

        $targetType = (string) ($neuron['target_type'] ?? '');
        $targetElementId = isset($neuron['target_element_id']) && $neuron['target_element_id'] !== null
            ? (int) $neuron['target_element_id']
            : null;

        $neuron['detection_result'] = $this->findDetectionTargetAroundElementHasPosition(
            $elementHasPosition,
            $range,
            $targetType,
            $targetElementId
        );
    }

    private function handlePathNeuron(array $neuron, $elementHasPosition): void
    {
        $neuronFrom = $neuron['neuron_from'] ?? null;
        if (!is_array($neuronFrom) || !isset($neuronFrom['id'])) {
            return;
        }

        $fromNeuronId = (int) $neuronFrom['id'];
        if ($fromNeuronId <= 0 || !isset($this->processedNeuronsById[$fromNeuronId])) {
            return;
        }

        $fromNeuron = $this->processedNeuronsById[$fromNeuronId];
        $fromDetectionResult = $fromNeuron['detection_result'] ?? null;
        if ($fromDetectionResult === null) {
            return;
        }

        $to = $this->parseCoordinateString((string) $fromDetectionResult);
        if ($to === null) {
            return;
        }

        $from = [
            'i' => (int) $elementHasPosition->tile_i,
            'j' => (int) $elementHasPosition->tile_j,
        ];

        $this->drawPathForPlayer((int) $elementHasPosition->player_id, $from, $to, $elementHasPosition);
    }

    private function handleAttackNeuron(array $neuron, ElementHasPosition $elementHasPosition): void
    {
        $resolvedDetectionResult = $this->resolveDetectionResultFromChain($neuron);
        if ($resolvedDetectionResult === null) {
            $neuron['attack_debug'] = 'no_detection_result_in_chain';
            return;
        }

        $target = $this->parseCoordinateString((string) $resolvedDetectionResult);
        if ($target === null) {
            $neuron['attack_debug'] = 'invalid_detection_coordinate';
            return;
        }

        $from = [
            'i' => (int) $elementHasPosition->tile_i,
            'j' => (int) $elementHasPosition->tile_j,
        ];

        $this->drawPathForPlayer((int) $elementHasPosition->player_id, $from, $target, $elementHasPosition);

        $attackGeneId = (int) ($neuron['gene_attack_id'] ?? 0);
        $lifeGeneId = (int) ($neuron['gene_life_id'] ?? 0);
        if ($attackGeneId <= 0) {
            $attackGeneId = (int) (Gene::query()->where('key', Gene::KEY_ATTACK)->value('id') ?? 0);
        }
        if ($lifeGeneId <= 0) {
            $lifeGeneId = (int) (Gene::query()->where('key', Gene::KEY_LIFEPOINT)->value('id') ?? 0);
        }
        if ($attackGeneId <= 0 || $lifeGeneId <= 0) {
            $neuron['attack_debug'] = 'missing_attack_or_life_gene';
            return;
        }

        $didAttack = $this->performAttackForElement((int) $elementHasPosition->player_id, $elementHasPosition, $target, $attackGeneId, $lifeGeneId);
        if ($didAttack) {
            $didRetreat = $this->moveElementAwayOneTile((int) $elementHasPosition->player_id, $elementHasPosition, $target);
            $neuron['attack_debug'] = $didRetreat ? 'ok_attack_and_retreat' : 'ok_attack_no_valid_retreat';
            return;
        }

        $neuron['attack_debug'] = 'attack_not_applied';
    }

    private function handleUnknownNeuron(array $neuron): void
    {

    }

    private function shouldProcessNeuronFromCondition(array $neuron): bool
    {
        $from = $neuron['neuron_from'] ?? null;
        if (!is_array($from)) {
            return true;
        }

        $condition = $from['condition'] ?? null;
        if ($condition === null || $condition === '') {
            return true;
        }
        if ($condition === 'found' || $condition === NeuronLink::PORT_DETECTION_SUCCESS) {
            $condition = NeuronLink::CONDITION_MAIN;
        } elseif ($condition === 'not_found' || $condition === NeuronLink::PORT_DETECTION_FAILURE) {
            $condition = NeuronLink::CONDITION_ELSE;
        }

        $fromId = isset($from['id']) ? (int) $from['id'] : 0;
        if ($fromId <= 0 || !isset($this->processedNeuronsById[$fromId])) {
            return false;
        }

        $fromNeuron = $this->processedNeuronsById[$fromId];
        if (($fromNeuron['is_active'] ?? true) === false) {
            return false;
        }
        if (($fromNeuron['type'] ?? null) !== Neuron::TYPE_DETECTION) {
            return true;
        }

        $detectionResult = $fromNeuron['detection_result'] ?? null;
        $hasDetection = is_string($detectionResult) && trim($detectionResult) !== '';

        if ($condition === NeuronLink::CONDITION_MAIN || $condition === NeuronLink::PORT_TRIGGER) {
            return $hasDetection;
        }
        if ($condition === NeuronLink::CONDITION_ELSE || $condition === NeuronLink::PORT_DETECTION_FAILURE) {
            return !$hasDetection;
        }

        return true;
    }

    private function hasActiveOutgoingLinks(array $neuron, array $outgoingLinks): bool
    {
        if (empty($outgoingLinks)) {
            return false;
        }

        $type = $neuron['type'] ?? null;

        if ($type === Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            return $this->hasActiveChemicalElementLinks($neuron, $outgoingLinks);
        }

        $detectionResult = $neuron['detection_result'] ?? null;
        $hasDetection = is_string($detectionResult) && trim($detectionResult) !== '';

        foreach ($outgoingLinks as $link) {
            $condition = $link->condition ?? null;
            if ($condition === 'found' || $condition === NeuronLink::PORT_DETECTION_SUCCESS) {
                $condition = NeuronLink::CONDITION_MAIN;
            } elseif ($condition === 'not_found' || $condition === NeuronLink::PORT_DETECTION_FAILURE) {
                $condition = NeuronLink::CONDITION_ELSE;
            }

            if ($type === Neuron::TYPE_DETECTION) {
                if (($condition === NeuronLink::CONDITION_MAIN || $condition === NeuronLink::PORT_DETECTION_SUCCESS) && $hasDetection) {
                    return true;
                }
                if (($condition === NeuronLink::CONDITION_ELSE || $condition === NeuronLink::PORT_DETECTION_FAILURE) && !$hasDetection) {
                    return true;
                }
                if ($condition === null || $condition === '' || $condition === NeuronLink::PORT_TRIGGER) {
                    return true;
                }
                continue;
            }

            return true;
        }

        return false;
    }

    private function hasActiveChemicalElementLinks(array $neuron, $outgoingLinks): bool
    {
        $value = $neuron['chemical_element_value'] ?? null;
        if ($value === null) {
            return false;
        }

        $hasRangeMatch = false;
        $hasDefaultLink = false;

        foreach ($outgoingLinks as $link) {
            $condition = $link->condition ?? null;

            if ($condition === NeuronLink::DEFAULT_CHIMICAL_ELEMENT) {
                $hasDefaultLink = true;
                continue;
            }

            $range = $this->parseConditionRange($condition);
            if ($range === null) {
                continue;
            }

            $min = $range['min'];
            $max = $range['max'];

            if ($value >= $min && $value <= $max) {
                $hasRangeMatch = true;
                break;
            }
        }

        return $hasRangeMatch || $hasDefaultLink;
    }

    private function isLinkActiveFromNeuron(array $neuron, $link): bool
    {
        $type = $neuron['type'] ?? null;
        $condition = $link->condition ?? null;

        if ($type === Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            return $this->isChemicalElementLinkActive($neuron, $link);
        }

        $detectionResult = $neuron['detection_result'] ?? null;
        $hasDetection = is_string($detectionResult) && trim($detectionResult) !== '';

        if ($condition === 'found' || $condition === NeuronLink::PORT_DETECTION_SUCCESS) {
            $condition = NeuronLink::CONDITION_MAIN;
        } elseif ($condition === 'not_found' || $condition === NeuronLink::PORT_DETECTION_FAILURE) {
            $condition = NeuronLink::CONDITION_ELSE;
        }

        if ($type === Neuron::TYPE_DETECTION) {
            if ($condition === NeuronLink::CONDITION_MAIN || $condition === NeuronLink::PORT_DETECTION_SUCCESS) {
                return $hasDetection;
            }
            if ($condition === NeuronLink::CONDITION_ELSE || $condition === NeuronLink::PORT_DETECTION_FAILURE) {
                return !$hasDetection;
            }
            return true;
        }

        return $condition === null || $condition === '' || $condition === NeuronLink::CONDITION_MAIN || $condition === NeuronLink::PORT_TRIGGER;
    }

    private function isChemicalElementLinkActive(array $neuron, $link): bool
    {
        $value = $neuron['chemical_element_value'] ?? null;
        if ($value === null) {
            return false;
        }

        $condition = $link->condition ?? null;

        if ($condition === NeuronLink::DEFAULT_CHIMICAL_ELEMENT) {
            return false;
        }

        $range = $this->parseConditionRange($condition);
        if ($range === null) {
            return false;
        }

        $min = $range['min'];
        $max = $range['max'];

        return $value >= $min && $value <= $max;
    }

    private function parseConditionRange(?string $condition): ?array
    {
        if ($condition === null || $condition === '') {
            Log::info('ParseRange: condition is empty');
            return null;
        }

        Log::info('ParseRange: input', ['condition' => $condition]);

        // Remove brackets and whitespace
        $cleaned = trim($condition, "[] \t\n\r\0\x0B");
        
        // Try both comma and forward slash as separator
        if (str_contains($cleaned, ',')) {
            $parts = explode(',', $cleaned);
        } elseif (str_contains($cleaned, '/')) {
            $parts = explode('/', $cleaned);
        } else {
            Log::info('ParseRange: no separator found');
            return null;
        }

        if (count($parts) !== 2) {
            Log::info('ParseRange: invalid parts count', ['parts' => $parts]);
            return null;
        }

        $min = trim($parts[0]);
        $max = trim($parts[1]);

        if (!is_numeric($min) || !is_numeric($max)) {
            Log::info('ParseRange: not numeric', ['min' => $min, 'max' => $max]);
            return null;
        }

        Log::info('ParseRange: success', ['min' => $min, 'max' => $max]);

        return [
            'min' => (int) $min,
            'max' => (int) $max,
        ];
    }


    private function processChemicalElementOutgoingLinks(array $neuronData, $outgoingLinks, int $currentId, array &$queue, array &$seen): bool
    {
        $value = $neuronData['chemical_element_value'] ?? null;
        Log::info('ProcessOutgoing: start', ['value' => $value, 'outgoing_count' => count($outgoingLinks)]);

        if ($value === null) {
            Log::info('ProcessOutgoing: value is null');
            return false;
        }

        $rangeLinks = [];
        $defaultLink = null;

        foreach ($outgoingLinks as $link) {
            $condition = $link->condition ?? null;
            Log::info('ProcessOutgoing: checking link', ['link_id' => $link->id, 'condition' => $condition]);

            if ($condition === NeuronLink::DEFAULT_CHIMICAL_ELEMENT) {
                $defaultLink = $link;
                Log::info('ProcessOutgoing: found default link');
                continue;
            }

            $range = $this->parseConditionRange($condition);
            if ($range === null) {
                Log::info('ProcessOutgoing: range is null', ['condition' => $condition]);
                continue;
            }

            $min = $range['min'];
            $max = $range['max'];
            Log::info('ProcessOutgoing: checking range', ['min' => $min, 'max' => $max, 'value' => $value]);

            if ($value >= $min && $value <= $max) {
                $rangeLinks[] = $link;
                Log::info('ProcessOutgoing: range matches!');
            }
        }

        $hasActive = false;
        if (!empty($rangeLinks)) {
            $hasActive = true;
            foreach ($rangeLinks as $link) {
                $toId = (int) $link->to_element_has_position_neuron_id;
                Log::info('ProcessOutgoing: adding range link to queue', ['toId' => $toId]);
                if ($toId > 0 && !isset($seen[$toId])) {
                    $queue[] = [
                        'id' => $toId,
                        'from' => [
                            'id' => $currentId,
                            'type' => $neuronData['type'] ?? null,
                            'condition' => $link->condition ?? null,
                        ],
                    ];
                }
            }
        } elseif ($defaultLink !== null) {
            $hasActive = true;
            $toId = (int) $defaultLink->to_element_has_position_neuron_id;
            Log::info('ProcessOutgoing: adding default link to queue', ['toId' => $toId]);
            if ($toId > 0 && !isset($seen[$toId])) {
                $queue[] = [
                    'id' => $toId,
                    'from' => [
                        'id' => $currentId,
                        'type' => $neuronData['type'] ?? null,
                        'condition' => $defaultLink->condition ?? null,
                    ],
                ];
            }
        } else {
            Log::info('ProcessOutgoing: no matching links found');
        }

        return $hasActive;
    }

    private function findDetectionTargetAroundElementHasPosition(
        ElementHasPosition $elementHasPosition,
        int $range,
        string $targetType,
        ?int $targetElementId
    ): ?string {
        $mapContainer = $this->resolveMapContainerForElementPosition($elementHasPosition);
        if ($mapContainer === null || empty($mapContainer->ws_port)) {
            return null;
        }

        $centerI = (int) $elementHasPosition->tile_i;
        $centerJ = (int) $elementHasPosition->tile_j;
        $coordinates = $this->buildRangeCoordinates($centerI, $centerJ, $range);
        if (empty($coordinates)) {
            return null;
        }

        $host = (string) $this->wsHost;
        $port = (int) $mapContainer->ws_port;
        $matches = [];

        try {
            $socket = $this->openMapWebSocket($host, $port);
            try {
                // Welcome frame from ws server.
                $this->readWebSocketFrame($socket);

                foreach ($coordinates as [$tileI, $tileJ]) {
                    $reply = $this->queryTileInfo($socket, $tileI, $tileJ);
                    if (!$this->isTileMatchingTarget($reply, $targetType, $targetElementId, $elementHasPosition)) {
                        continue;
                    }

                    $distance = abs($tileI - $centerI) + abs($tileJ - $centerJ);
                    $matches[] = [
                        'i' => $tileI,
                        'j' => $tileJ,
                        'distance' => $distance,
                    ];
                }
            } finally {
                fclose($socket);
            }
        } catch (\Throwable $e) {
            Log::info($e);
            return null;
        }

        Log::info('matches ' . json_encode($matches));
        if (empty($matches)) {
            return null;
        }

        usort($matches, function (array $a, array $b): int {
            if ($a['distance'] !== $b['distance']) {
                return $a['distance'] <=> $b['distance'];
            }
            if ($a['i'] !== $b['i']) {
                return $a['i'] <=> $b['i'];
            }
            return $a['j'] <=> $b['j'];
        });

        $best = $matches[0];
        return '(' . $best['i'] . ',' . $best['j'] . ')';
    }

    private function isLiquidTileAt($tiles, int $tileI, int $tileJ): bool
    {
        if ($tiles instanceof \Illuminate\Support\Collection) {
            $tile = $tiles->where('i', $tileI)->where('j', $tileJ)->first();
            return is_array($tile)
                && isset($tile['tile']['type'])
                && $tile['tile']['type'] === Tile::TYPE_LIQUID;
        }

        return false;
    }

    private function resolveMapContainerForElementPosition(ElementHasPosition $elementHasPosition): ?Container
    {
        $player = $elementHasPosition->player()->first();
        if ($player === null || empty($player->birth_region_id)) {
            return null;
        }

        return Container::query()
            ->where('parent_type', Container::PARENT_TYPE_MAP)
            ->where('parent_id', (int) $player->birth_region_id)
            ->whereNotNull('ws_port')
            ->orderByDesc('id')
            ->first();
    }

    private function buildRangeCoordinates(int $centerI, int $centerJ, int $range): array
    {
        $coordinates = [];
        for ($i = $centerI - $range; $i <= $centerI + $range; $i++) {
            for ($j = $centerJ - $range; $j <= $centerJ + $range; $j++) {
                if ($i < 0 || $j < 0) {
                    continue;
                }
                $coordinates[] = [$i, $j];
            }
        }
        return $coordinates;
    }

    private function isTileMatchingTarget(
        array $reply,
        string $targetType,
        ?int $targetElementId,
        ElementHasPosition $selfElementHasPosition
    ): bool {
        $isSuccess = (bool) ($reply['success'] ?? false);
        if (!$isSuccess) {
            return false;
        }

        $tile = $reply['tile'] ?? null;
        if (!is_array($tile)) {
            return false;
        }
        if (($tile['tile']['type'] ?? null) !== Tile::TYPE_LIQUID) {
            return false;
        }

        if ($targetType === Neuron::TARGET_TYPE_ENTITY) {
            return !empty($tile['entity']);
        }

        if ($targetType === Neuron::TARGET_TYPE_ELEMENT) {
            if (empty($tile['element']) || !is_array($tile['element'])) {
                return false;
            }

            $tileElement = $tile['element'];
            $tileElementId = isset($tileElement['id']) ? (int) $tileElement['id'] : null;
            if ($targetElementId !== null && $targetElementId > 0 && $tileElementId !== $targetElementId) {
                return false;
            }

            // Ignore self element position.
            $tileUid = isset($tileElement['uid']) ? (string) $tileElement['uid'] : '';
            if ($tileUid !== '' && $tileUid === (string) $selfElementHasPosition->uid) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function openMapWebSocket(string $host, int $targetPort)
    {
        $socket = @stream_socket_client(
            "tcp://{$host}:{$this->websocketGatewayPort}",
            $errno,
            $errstr,
            5
        );

        if ($socket === false) {
            throw new \RuntimeException("Connessione websocket al gateway fallita ({$errno}): {$errstr}");
        }

        stream_set_timeout($socket, 5);
        $this->performWebSocketHandshake($socket, $host, $this->websocketGatewayPort, $targetPort);

        return $socket;
    }

    private function queryTileInfo($socket, int $tileI, int $tileJ): array
    {
        $payload = [
            'command' => 'get_tile_info',
            'params' => [
                'tile_i' => $tileI,
                'tile_j' => $tileJ,
            ],
        ];
        $this->writeWebSocketFrame($socket, json_encode($payload));

        $replyRaw = $this->readWebSocketFrame($socket);
        if ($replyRaw === null) {
            return ['success' => false];
        }

        $reply = json_decode($replyRaw, true);
        if (!is_array($reply)) {
            return ['success' => false];
        }

        return $reply;
    }

    private function performWebSocketHandshake($socket, string $host, int $gatewayPort, int $targetPort): void
    {
        $key = base64_encode(random_bytes(16));
        $headers = "GET /?port={$targetPort} HTTP/1.1\r\n";
        $headers .= "Host: {$host}:{$gatewayPort}\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Key: {$key}\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n\r\n";

        fwrite($socket, $headers);

        $response = '';
        while (!str_contains($response, "\r\n\r\n")) {
            $chunk = fread($socket, 1024);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $response .= $chunk;
        }

        if (!str_contains($response, ' 101 ')) {
            throw new \RuntimeException('Handshake websocket fallito: ' . trim($response));
        }
    }

    private function writeWebSocketFrame($socket, string $payload): void
    {
        $payloadLength = strlen($payload);
        $frame = chr(0x81);

        if ($payloadLength <= 125) {
            $frame .= chr(0x80 | $payloadLength);
        } elseif ($payloadLength <= 65535) {
            $frame .= chr(0x80 | 126) . pack('n', $payloadLength);
        } else {
            $frame .= chr(0x80 | 127) . pack('NN', 0, $payloadLength);
        }

        $mask = random_bytes(4);
        $frame .= $mask;

        $maskedPayload = '';
        for ($i = 0; $i < $payloadLength; $i++) {
            $maskedPayload .= $payload[$i] ^ $mask[$i % 4];
        }

        fwrite($socket, $frame . $maskedPayload);
    }

    private function readWebSocketFrame($socket): ?string
    {
        $header = $this->readExactBytes($socket, 2);
        if ($header === null) {
            return null;
        }

        $first = ord($header[0]);
        $second = ord($header[1]);

        $opcode = $first & 0x0f;
        $isMasked = ($second & 0x80) !== 0;
        $payloadLength = $second & 0x7f;

        if ($payloadLength === 126) {
            $extended = $this->readExactBytes($socket, 2);
            if ($extended === null) {
                return null;
            }
            $payloadLength = unpack('n', $extended)[1];
        } elseif ($payloadLength === 127) {
            $extended = $this->readExactBytes($socket, 8);
            if ($extended === null) {
                return null;
            }
            $parts = unpack('N2', $extended);
            $payloadLength = ($parts[1] << 32) + $parts[2];
        }

        $mask = '';
        if ($isMasked) {
            $mask = $this->readExactBytes($socket, 4) ?? '';
        }

        $payload = $payloadLength > 0 ? $this->readExactBytes($socket, (int) $payloadLength) : '';
        if ($payload === null) {
            return null;
        }

        if ($isMasked && $mask !== '') {
            $decoded = '';
            $len = strlen($payload);
            for ($i = 0; $i < $len; $i++) {
                $decoded .= $payload[$i] ^ $mask[$i % 4];
            }
            $payload = $decoded;
        }

        if ($opcode === 0x8) {
            return null;
        }

        return $payload;
    }

    private function readExactBytes($socket, int $length): ?string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                return null;
            }
            $data .= $chunk;
        }
        return $data;
    }

    private function parseCoordinateString(string $value): ?array
    {
        if (!preg_match('/^\(\s*(\d+)\s*,\s*(\d+)\s*\)$/', trim($value), $matches)) {
            return null;
        }

        return [
            'i' => (int) $matches[1],
            'j' => (int) $matches[2],
        ];
    }

    private function drawPathForPlayer(int $playerId, array $from, array $to, ElementHasPosition $elementHasPosition, bool $stopBeforeTarget = true): void
    {
        $player = Player::query()->with('birthRegion')->find($playerId);
        if ($player === null || $player->birthRegion === null || empty($player->actual_session_id)) {
            return;
        }

        $tiles = Helper::getBirthRegionTiles($player->birthRegion);
        $mapSolidTiles = Helper::getMapSolidTiles($tiles, $player->birthRegion);
        $mapSolidTiles[(int) $from['i']][(int) $from['j']] = 'A';
        $mapSolidTiles[(int) $to['i']][(int) $to['j']] = 'B';

        $pathFinding = Helper::calculatePathFinding($mapSolidTiles);
        if (!is_array($pathFinding) || empty($pathFinding)) {
            return;
        }

        if ($stopBeforeTarget) {
            // Stop one tile before the detection target.
            if (count($pathFinding) > 1) {
                array_pop($pathFinding);
            }
        }
        if (empty($pathFinding)) {
            return;
        }

        $sessionId = (string) $player->actual_session_id;
        $elementUid = (string) $elementHasPosition->uid;
        $updateCommands = [];
        $idsToClear = [];
        $drawCommands = [];
        ObjectCache::buffer($sessionId);

        foreach ($pathFinding as $key => $path) {
            $pathNodeI = (int) $path[0];
            $pathNodeJ = (int) $path[1];
            $elementHasPosition->update([
                'tile_i' => $pathNodeI,
                'tile_j' => $pathNodeJ,
            ]);

            $tileSize = Helper::TILE_SIZE;

            $originX = ($tileSize * $pathNodeJ) + Helper::MAP_START_X;
            $originY = ($tileSize * $pathNodeI) + Helper::MAP_START_Y;

            $startSquare = new Square();
            $startSquare->setOrigin($originX, $originY);
            $startSquare->setSize($tileSize);
            $startCenterSquare = $startSquare->getCenter();
            $xStart = $startCenterSquare['x'];
            $yStart = $startCenterSquare['y'];

            $circleName = 'brain_path_circle_' . Str::random(20);
            $idsToClear[] = $circleName;
            $circle = new Circle($circleName);
            $circle->setOrigin($xStart, $yStart);
            $circle->setRadius($tileSize / 6);
            $circle->setColor('#FF0000');
            $drawCommands[] = $this->drawMapGroupObject($circle, $sessionId);

            if ((count($pathFinding) - 1) === $key) {
                continue;
            }

            $nextPathNodeI = (int) $pathFinding[$key + 1][0];
            $nextPathNodeJ = (int) $pathFinding[$key + 1][1];
            $endX = ($tileSize * $nextPathNodeJ) + Helper::MAP_START_X;
            $endY = ($tileSize * $nextPathNodeI) + Helper::MAP_START_Y;

            $endSquare = new Square();
            $endSquare->setSize($tileSize);
            $endSquare->setOrigin($endX, $endY);
            $endCenterSquare = $endSquare->getCenter();
            $xEnd = $endCenterSquare['x'];
            $yEnd = $endCenterSquare['y'];

            $lineName = 'brain_path_line_' . Str::random(20);
            $idsToClear[] = $lineName;
            $linePath = new MultiLine($lineName);
            $linePath->setPoint($xStart, $yStart);
            $linePath->setPoint($xEnd, $yEnd);
            $linePath->setColor('#FF0000');
            $linePath->setThickness(2);
            $drawCommands[] = $this->drawMapGroupObject($linePath, $sessionId);

            $updateObject = new ObjectUpdate($elementUid, $sessionId, 250);
            // ElementDraw uses tile top-left origin, not tile center.
            $updateObject->setAttributes('x', $endX);
            $updateObject->setAttributes('y', $endY);
            $updateObject->setAttributes('zIndex', 100);
            foreach ($updateObject->get() as $data) {
                $updateCommands[] = $data;
            }

        }

        foreach ($updateCommands as $update) {
            $drawCommands[] = $update;
        }
        foreach ($idsToClear as $idToClear) {
            $clearObject = new ObjectClear($idToClear, $sessionId);
            $drawCommands[] = $clearObject->get();
            ObjectCache::forget($sessionId, $idToClear);
        }

        ObjectCache::flush($sessionId);
        $this->queueDrawCommands($player, $sessionId, $drawCommands);
    }

    private function performAttackForElement(
        int $playerId,
        ElementHasPosition $attackerElementPosition,
        array $targetTile,
        int $attackGeneId,
        int $lifeGeneId
    ): bool {
        $player = Player::query()->find($playerId);
        if ($player === null || empty($player->actual_session_id)) {
            return false;
        }

        $targetEntity = Entity::query()
            ->where('state', Entity::STATE_LIFE)
            ->where('tile_i', (int) $targetTile['i'])
            ->where('tile_j', (int) $targetTile['j'])
            ->first();

        $attackInfo = ElementHasPositionInformation::query()
            ->where('element_has_position_id', $attackerElementPosition->id)
            ->where('gene_id', $attackGeneId)
            ->first();
        $damage = (int) ($attackInfo->value ?? 0);
        if ($damage <= 0) {
            return false;
        }

        $sessionId = (string) $player->actual_session_id;
        $drawCommands = [];
        ObjectCache::buffer($sessionId);

        $lifeGene = Gene::query()->find($lifeGeneId);
        $newLife = null;
        $targetType = null;
        $targetUid = null;
        $targetEntityId = null;
        $targetElementPosition = null;

        if ($targetEntity !== null) {
            $lifeGenome = Genome::query()
                ->where('entity_id', $targetEntity->id)
                ->where('gene_id', $lifeGeneId)
                ->first();
            if ($lifeGenome === null) {
                ObjectCache::flush($sessionId);
                return false;
            }

            $targetLifeInfo = EntityInformation::query()
                ->where('genome_id', $lifeGenome->id)
                ->first();
            if ($targetLifeInfo === null) {
                ObjectCache::flush($sessionId);
                return false;
            }

            $newLife = ((int) $targetLifeInfo->value) - $damage;
            $targetType = 'entity';
            $targetUid = (string) $targetEntity->uid;
            $targetEntityId = (int) $targetEntity->id;

            $updateItems = [['id' => $targetLifeInfo->id, 'type' => 'entity', 'attributes' => ['value' => $newLife]]];
            $updateCode = $this->buildUpdateInfoCode($updateItems);
            if ($updateCode !== '') {
                $drawCommands[] = (new ObjectCode($updateCode, 500))->get();
            }
        } else {
            $targetElementPosition = ElementHasPosition::query()
                ->where('tile_i', (int) $targetTile['i'])
                ->where('tile_j', (int) $targetTile['j'])
                ->where('id', '!=', (int) $attackerElementPosition->id)
                ->first();
            if ($targetElementPosition === null) {
                ObjectCache::flush($sessionId);
                return false;
            }

            $targetLifeInfo = ElementHasPositionInformation::query()
                ->where('element_has_position_id', $targetElementPosition->id)
                ->where('gene_id', $lifeGeneId)
                ->first();
            if ($targetLifeInfo === null) {
                ObjectCache::flush($sessionId);
                return false;
            }

            $newLife = ((int) $targetLifeInfo->value) - $damage;
            $targetType = 'element';
            $targetUid = (string) $targetElementPosition->uid;

            $updateItems = [['id' => $targetLifeInfo->id, 'type' => 'element', 'attributes' => ['value' => $newLife]]];
            $updateCode = $this->buildUpdateInfoCode($updateItems);
            if ($updateCode !== '') {
                $drawCommands[] = (new ObjectCode($updateCode, 500))->get();
            }
        }


        if ($newLife !== null && $newLife <= 0) {
            if ($targetType === 'entity' && $targetEntity !== null) {
                $targetEntity->update(['state' => Entity::STATE_DEATH]);
                $fallbackUids = $this->buildEntityClearFallbackUids($targetEntity, (string) $targetUid);
                $idsToClear = $this->resolveDrawUidsForObject($sessionId, (string) $targetUid, $fallbackUids);

                foreach ($idsToClear as $idToClear) {
                    $clearObject = new ObjectClear($idToClear, $sessionId);
                    $drawCommands[] = $clearObject->get();
                    ObjectCache::forget($sessionId, $idToClear);
                }

                if ($targetEntityId !== null) {
                    $this->stopContainerByParent(Container::PARENT_TYPE_ENTITY, $targetEntityId);
                }
            }

            if ($targetType === 'element' && $targetElementPosition !== null) {
                $fallbackUids = $this->buildElementClearFallbackUids($targetElementPosition, (string) $targetUid);
                $idsToClear = $this->resolveDrawUidsForObject($sessionId, (string) $targetUid, $fallbackUids);
                foreach ($idsToClear as $idToClear) {
                    $clearObject = new ObjectClear($idToClear, $sessionId);
                    $drawCommands[] = $clearObject->get();
                    ObjectCache::forget($sessionId, $idToClear);
                }

                // Explicit stop requested for element death.
                $this->stopContainerByParent(Container::PARENT_TYPE_ELEMENT_HAS_POSITION, (int) $targetElementPosition->id);
                $targetElementPosition->delete();
            }
        }

        ObjectCache::flush($sessionId);
        $this->queueDrawCommands($player, $sessionId, $drawCommands);
        return true;
    }

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
            ->where('entity_id', $targetEntity->id)
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

    private function buildElementClearFallbackUids(ElementHasPosition $targetElementPosition, string $targetUid): array
    {
        $fallback = [
            $targetUid,
            $targetUid . '_panel',
            $targetUid . '_text_name',
            $targetUid . '_btn_attack',
            $targetUid . '_btn_attack_rect',
            $targetUid . '_btn_attack_text',
            $targetUid . '_btn_consume',
            $targetUid . '_btn_consume_rect',
            $targetUid . '_btn_consume_text',
        ];

        $elementInfos = ElementHasPositionInformation::query()
            ->where('element_has_position_id', $targetElementPosition->id)
            ->with(['gene'])
            ->get();
        foreach ($elementInfos as $elementInfo) {
            $gene = $elementInfo->gene;
            if ($gene === null) {
                continue;
            }
            $progressUid = 'gene_progress_' . $gene->key . '_element_' . $targetUid;
            $fallback[] = $progressUid;
            $fallback[] = $progressUid . '_border';
            $fallback[] = $progressUid . '_bar';
            $fallback[] = $progressUid . '_text';
            $fallback[] = $progressUid . '_range';
        }

        return array_values(array_unique($fallback));
    }

    private function moveElementAwayOneTile(int $playerId, ElementHasPosition $elementHasPosition, array $targetTile): bool
    {
        $player = Player::query()->with('birthRegion')->find($playerId);
        if ($player === null || $player->birthRegion === null || empty($player->actual_session_id)) {
            return false;
        }

        $currentI = (int) $elementHasPosition->tile_i;
        $currentJ = (int) $elementHasPosition->tile_j;
        $targetI = (int) ($targetTile['i'] ?? $currentI);
        $targetJ = (int) ($targetTile['j'] ?? $currentJ);

        $awayI = $currentI <=> $targetI;
        $awayJ = $currentJ <=> $targetJ;

        $candidateDirections = [];
        if ($awayI !== 0) {
            $candidateDirections[] = [$awayI, 0];
        }
        if ($awayJ !== 0) {
            $candidateDirections[] = [0, $awayJ];
        }
        $candidateDirections[] = [-1, 0];
        $candidateDirections[] = [1, 0];
        $candidateDirections[] = [0, -1];
        $candidateDirections[] = [0, 1];

        $uniqueDirections = [];
        $seen = [];
        foreach ($candidateDirections as $dir) {
            $key = $dir[0] . '_' . $dir[1];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $uniqueDirections[] = $dir;
        }

        $birthRegion = $player->birthRegion;
        $tiles = Helper::getBirthRegionTiles($birthRegion);
        $solidMap = Helper::getMapSolidTiles($tiles, $birthRegion);

        $destination = null;
        foreach ($uniqueDirections as [$di, $dj]) {
            $nextI = $currentI + $di;
            $nextJ = $currentJ + $dj;

            if ($nextI < 0 || $nextJ < 0 || $nextI >= (int) $birthRegion->height || $nextJ >= (int) $birthRegion->width) {
                continue;
            }

            if (($solidMap[$nextI][$nextJ] ?? 'X') !== '0') {
                continue;
            }
            if (!$this->isLiquidTileAt($tiles, $nextI, $nextJ)) {
                continue;
            }

            $hasLiveEntity = Entity::query()
                ->where('state', Entity::STATE_LIFE)
                ->where('tile_i', $nextI)
                ->where('tile_j', $nextJ)
                ->exists();
            if ($hasLiveEntity) {
                continue;
            }

            $hasElement = ElementHasPosition::query()
                ->where('id', '!=', (int) $elementHasPosition->id)
                ->where('tile_i', $nextI)
                ->where('tile_j', $nextJ)
                ->exists();
            if ($hasElement) {
                continue;
            }

            $destination = ['i' => $nextI, 'j' => $nextJ];
            break;
        }

        if ($destination === null) {
            return false;
        }

        $elementHasPosition->update([
            'tile_i' => (int) $destination['i'],
            'tile_j' => (int) $destination['j'],
        ]);

        $tileSize = Helper::TILE_SIZE;
        $endX = ($tileSize * (int) $destination['j']) + Helper::MAP_START_X;
        $endY = ($tileSize * (int) $destination['i']) + Helper::MAP_START_Y;
        $panelX = $endX + ($tileSize / 2);
        $panelY = $endY + ($tileSize / 2);
        $elementUid = (string) $elementHasPosition->uid;
        $sessionId = (string) $player->actual_session_id;

        ObjectCache::buffer($sessionId);
        $drawCommands = [];

        // Panel movement must be immediate.
        $updateObject = new ObjectUpdate($elementUid, $sessionId, 0);
        $updateObject->setAttributes('x', $endX);
        $updateObject->setAttributes('y', $endY);
        $updateObject->setAttributes('zIndex', 100);
        foreach ($updateObject->get() as $data) {
            $drawCommands[] = $data;
        }

        ObjectCache::flush($sessionId);
        $this->queueDrawCommands($player, $sessionId, $drawCommands);

        return true;
    }

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

    private function resolveDetectionResultFromChain(array $neuron): ?string
    {
        $current = $neuron;
        for ($depth = 0; $depth < 20; $depth++) {
            $detectionResult = $current['detection_result'] ?? null;
            if (is_string($detectionResult) && trim($detectionResult) !== '') {
                return $detectionResult;
            }

            $from = $current['neuron_from'] ?? null;
            if (!is_array($from) || !isset($from['id'])) {
                return null;
            }
            $fromId = (int) $from['id'];
            if ($fromId <= 0 || !isset($this->processedNeuronsById[$fromId])) {
                return null;
            }

            $current = $this->processedNeuronsById[$fromId];
        }

        return null;
    }

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
            Log::warning('Unable to stop/delete container for dead target', [
                'parent_type' => $parentType,
                'parent_id' => $parentId,
                'container_id' => $container->container_id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $container->delete();
        }
    }

    private function queueDrawCommands(Player $player, string $sessionId, array $drawCommands): void
    {
        if (empty($drawCommands)) {
            return;
        }

        if (!isset($this->queuedDrawBySession[$sessionId])) {
            $this->queuedDrawBySession[$sessionId] = [
                'player' => $player,
                'player_id' => (int) $player->id,
                'items' => [],
            ];
        }

        foreach ($drawCommands as $drawCommand) {
            $this->queuedDrawBySession[$sessionId]['items'][] = $drawCommand;
        }
    }

    private function dispatchQueuedDrawRequests(): void
    {
        foreach ($this->queuedDrawBySession as $sessionId => $payload) {
            $items = $payload['items'] ?? [];
            if (empty($items)) {
                continue;
            }

            $playerId = (int) ($payload['player_id'] ?? 0);
            if ($playerId > 0) {

                $requestId = Str::random(20);
                DrawRequest::query()->create([
                    'session_id' => (string) $sessionId,
                    'request_id' => $requestId,
                    'player_id' => $playerId,
                    'items' => json_encode($items),
                ]);

            }

        }
    }

    private function markBrainScheduleFinished(int $elementHasPositionId): void
    {
        if ($elementHasPositionId <= 0) {
            return;
        }

        BrainSchedule::query()
            ->where('element_has_position_id', $elementHasPositionId)
            ->whereIn('state', [BrainSchedule::STATE_CREATE, BrainSchedule::STATE_IN_PROGRESS])
            ->update(['state' => BrainSchedule::STATE_FINISH]);
    }

    private function queueCodeAtEndOfQueuedDraws(string $code): void
    {
        if (trim($code) === '') {
            return;
        }

        foreach ($this->queuedDrawBySession as &$payload) {
            $payload['items'][] = (new ObjectCode($code))->get();
        }
        unset($payload);
    }

    private function getEndOfTestBrainCode(): string
    {
        $jsPath = resource_path('js/function/brain/on_finish_brain_schedule.blade.php');
        if (!is_file($jsPath)) {
            return '';
        }

        $jsContent = file_get_contents($jsPath);
        if ($jsContent === false) {
            return '';
        }

        $jsContent = str_replace('__ELEMENT_HAS_POSITION_ID__', (string) $this->elementHasPositionId, $jsContent);
        return Helper::setCommonJsCode($jsContent, Str::random(20));
    }

    private function drawMapGroupObject($objectOrArray, string $sessionId): array
    {
        $objectArray = is_array($objectOrArray) ? $objectOrArray : $objectOrArray->buildJson();
        $objectArray = ScrollGroup::attach($objectArray, Helper::MAP_SCROLL_GROUP_MAIN);

        $drawObject = new ObjectDraw($objectArray, $sessionId);
        return $drawObject->get();
    }

    private function buildUpdateInfoCode(array $updateItems): string
    {
        if (empty($updateItems)) {
            return '';
        }

        $updateItemsJson = json_encode($updateItems);

        $firstItem = reset($updateItems);
        $type = $firstItem['type'] ?? 'entity';

        $bladeFile = $type === 'element' ? 'element/update_info.blade.php' : 'entity/update_info.blade.php';
        $jsPath = resource_path('js/function/' . $bladeFile);

        if (is_file($jsPath)) {
            $jsContent = file_get_contents($jsPath);
            if ($jsContent !== false) {
                $jsContent = str_replace('__UPDATE_ITEMS__', $updateItemsJson, $jsContent);
                return Helper::setCommonJsCode($jsContent, Str::random(20));
            }
        }

        return '';
    }
}


