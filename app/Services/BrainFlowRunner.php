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
use App\Models\Neuron;
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
use App\Events\DrawInterfaceEvent;
use App\Helper\Helper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Docker\Docker;

class BrainFlowRunner
{
    private array $processedNeuronsById = [];
    private array $queuedDrawBySession = [];
    private int $elementHasPositionId = 0;
    private string $wsHost = '127.0.0.1';

    public function run(int $elementHasPositionId, string $wsHost = '127.0.0.1'): array
    {
        $this->elementHasPositionId = $elementHasPositionId;
        $this->wsHost = $wsHost;

        $item = ElementHasPosition::query()
            ->with([
                'brain.neurons.outgoingLinks',
                'brain.neurons.incomingLinks',
            ])
            ->find($elementHasPositionId);

        if ($item === null) {
            throw new \RuntimeException("ElementHasPosition {$elementHasPositionId} non trovato.");
        }

        $orderedFlow = $this->buildOrderedNeuronsWithFromLink($item);
        $this->processedNeuronsById = [];
        $this->queuedDrawBySession = [];
        foreach ($orderedFlow as &$orderedNeuron) {
            $this->handleNeuronByType($orderedNeuron, $item);
            if (isset($orderedNeuron['id'])) {
                $this->processedNeuronsById[(int) $orderedNeuron['id']] = $orderedNeuron;
            }
        }
        unset($orderedNeuron);

        $this->queueCodeAtEndOfQueuedDraws($this->getEndOfTestBrainCode());
        $this->dispatchQueuedDrawRequests();
        return $orderedFlow;
    }

    private function buildOrderedNeuronsWithFromLink(ElementHasPosition $elementHasPosition): array
    {
        $brain = $elementHasPosition->brain;
        if ($brain === null) {
            return [];
        }

        $neurons = $brain->neurons
            ->sortBy(fn (ElementHasPositionNeuron $n) => sprintf('%05d_%05d_%010d', (int) $n->grid_i, (int) $n->grid_j, (int) $n->id))
            ->values();

        $result = [];
        $neuronsById = $neurons->keyBy('id');

        foreach ($neurons as $current) {
            /** @var ElementHasPositionNeuron $current */
            $neuronFrom = null;
            foreach ($current->incomingLinks->sortBy('id') as $link) {
                /** @var ElementHasPositionNeuron|null $toNeuron */
                $fromNeuron = $neuronsById->get((int) $link->from_element_has_position_neuron_id);

                if ($fromNeuron !== null) {
                    $neuronFrom = [
                        'id' => (int) $fromNeuron->id,
                        'type' => $fromNeuron->type,
                    ];
                    break;
                }

                $neuronFrom = [
                    'id' => (int) $link->from_element_has_position_neuron_id,
                    'type' => null,
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
        switch ($neuron['type'] ?? null) {
            case Neuron::TYPE_DETECTION:
                $this->handleDetectionNeuron($neuron);
                break;
            case Neuron::TYPE_PATH:
                $this->handlePathNeuron($neuron, $elementHasPosition);
                break;
            case Neuron::TYPE_ATTACK:
                $this->handleAttackNeuron($neuron, $elementHasPosition);
                break;
            default:
                $this->handleUnknownNeuron($neuron);
                break;
        }
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

        if (empty($matches)) {
            return $this->findDetectionTargetAroundElementHasPositionFromDb(
                $elementHasPosition,
                $range,
                $targetType,
                $targetElementId
            );
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

    private function findDetectionTargetAroundElementHasPositionFromDb(
        ElementHasPosition $elementHasPosition,
        int $range,
        string $targetType,
        ?int $targetElementId
    ): ?string {
        $minI = max(0, (int) $elementHasPosition->tile_i - $range);
        $maxI = max(0, (int) $elementHasPosition->tile_i + $range);
        $minJ = max(0, (int) $elementHasPosition->tile_j - $range);
        $maxJ = max(0, (int) $elementHasPosition->tile_j + $range);
        $centerI = (int) $elementHasPosition->tile_i;
        $centerJ = (int) $elementHasPosition->tile_j;

        $matches = [];

        if ($targetType === Neuron::TARGET_TYPE_ENTITY) {
            $entities = Entity::query()
                ->where('state', Entity::STATE_LIFE)
                ->whereBetween('tile_i', [$minI, $maxI])
                ->whereBetween('tile_j', [$minJ, $maxJ])
                ->get(['tile_i', 'tile_j']);

            foreach ($entities as $entity) {
                $tileI = (int) $entity->tile_i;
                $tileJ = (int) $entity->tile_j;
                $matches[] = [
                    'i' => $tileI,
                    'j' => $tileJ,
                    'distance' => abs($tileI - $centerI) + abs($tileJ - $centerJ),
                ];
            }
        } elseif ($targetType === Neuron::TARGET_TYPE_ELEMENT) {
            $query = ElementHasPosition::query()
                ->whereBetween('tile_i', [$minI, $maxI])
                ->whereBetween('tile_j', [$minJ, $maxJ])
                ->where('id', '!=', (int) $elementHasPosition->id);

            if ($targetElementId !== null && $targetElementId > 0) {
                $query->where('element_id', $targetElementId);
            }

            $elements = $query->get(['tile_i', 'tile_j']);
            foreach ($elements as $element) {
                $tileI = (int) $element->tile_i;
                $tileJ = (int) $element->tile_j;
                $matches[] = [
                    'i' => $tileI,
                    'j' => $tileJ,
                    'distance' => abs($tileI - $centerI) + abs($tileJ - $centerJ),
                ];
            }
        }

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

    private function openMapWebSocket(string $host, int $port)
    {
        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            5
        );

        if ($socket === false) {
            throw new \RuntimeException("Connessione websocket fallita ({$errno}): {$errstr}");
        }

        stream_set_timeout($socket, 5);
        $this->performWebSocketHandshake($socket, $host, $port);

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

    private function performWebSocketHandshake($socket, string $host, int $port): void
    {
        $key = base64_encode(random_bytes(16));
        $headers = "GET / HTTP/1.1\r\n";
        $headers .= "Host: {$host}:{$port}\r\n";
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

    private function drawPathForPlayer(int $playerId, array $from, array $to, ElementHasPosition $elementHasPosition): void
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

        // Stop one tile before the detection target.
        if (count($pathFinding) > 1) {
            array_pop($pathFinding);
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

            $panelX = $endX + ($tileSize / 2);
            $panelY = $endY + ($tileSize / 2);
            $updateObject = new ObjectUpdate($elementUid . '_panel', $sessionId);
            $updateObject->setAttributes('x', $panelX);
            $updateObject->setAttributes('y', $panelY);
            $updateObject->setAttributes('zIndex', 100);
            foreach ($updateObject->get() as $data) {
                $updateCommands[] = $data;
            }

            $updateObject = new ObjectUpdate($elementUid . '_text_name', $sessionId);
            $updateObject->setAttributes('x', $panelX + 10);
            $updateObject->setAttributes('y', $panelY + 10);
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

            $targetLifeInfo->update(['value' => ((int) $targetLifeInfo->value) - $damage]);
            $newLife = (int) $targetLifeInfo->value;
            $targetType = 'entity';
            $targetUid = (string) $targetEntity->uid;
            $targetEntityId = (int) $targetEntity->id;
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

            $targetLifeInfo->update(['value' => ((int) $targetLifeInfo->value) - $damage]);
            $newLife = (int) $targetLifeInfo->value;
            $targetType = 'element';
            $targetUid = (string) $targetElementPosition->uid;
        }

        if ($lifeGene !== null && $targetUid !== null) {
            $progressBarUid = $targetType === 'entity'
                ? ($targetUid . '_progress_bar_' . $lifeGene->key)
                : ('gene_progress_' . $lifeGene->key . '_element_' . $targetUid);
            $isPanelRenderable = $this->isTargetPanelRenderable($sessionId, $targetUid);
            try {
                $progressBar = new ProgressBarDraw($progressBarUid);
                $operations = $progressBar->updateValue($newLife, $sessionId);
                foreach ($operations as $operation) {
                    if (($operation['type'] ?? null) === 'update') {
                        if ($isPanelRenderable === false) {
                            if (!isset($operation['attributes']) || !is_array($operation['attributes'])) {
                                $operation['attributes'] = [];
                            }
                            $operation['attributes']['renderable'] = false;
                        }
                        $updateObject = new ObjectUpdate($operation['uid'], $sessionId);
                        foreach (($operation['attributes'] ?? []) as $attribute => $value) {
                            $updateObject->setAttributes($attribute, $value);
                        }
                        foreach ($updateObject->get() as $data) {
                            $drawCommands[] = $data;
                        }
                        continue;
                    }

                    if (($operation['type'] ?? null) === 'draw') {
                        if ($isPanelRenderable === false) {
                            continue;
                        }
                        $drawCommands[] = $this->drawMapGroupObject($operation['object'], $sessionId);
                        continue;
                    }

                    if (($operation['type'] ?? null) === 'clear') {
                        $clearObject = new ObjectClear($operation['uid'], $sessionId);
                        $drawCommands[] = $clearObject->get();
                        ObjectCache::forget($sessionId, $operation['uid']);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('ProgressBar update failed in handleAttackNeuron', ['error' => $e->getMessage()]);
            }
        }

        if ($newLife !== null && $newLife <= 0) {
            if ($targetType === 'entity' && $targetEntity !== null) {
                $targetEntity->update(['state' => Entity::STATE_DEATH]);
                $idsToClear = $this->resolveDrawUidsForObject($sessionId, (string) $targetUid, [
                    (string) $targetUid,
                    $targetUid . '_panel',
                    $targetUid . '_text_row_1',
                    $targetUid . '_text_row_2',
                    $targetUid . '_button_division',
                    $targetUid . '_button_division_rect',
                    $targetUid . '_button_division_text',
                ]);

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
                $idsToClear = $this->resolveDrawUidsForObject($sessionId, (string) $targetUid, [
                    (string) $targetUid,
                    $targetUid . '_panel',
                    $targetUid . '_text_name',
                    $targetUid . '_btn_attack',
                    $targetUid . '_btn_attack_rect',
                    $targetUid . '_btn_attack_text',
                    $targetUid . '_btn_consume',
                    $targetUid . '_btn_consume_rect',
                    $targetUid . '_btn_consume_text',
                ]);
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

        $updateObject = new ObjectUpdate($elementUid, $sessionId, 250);
        $updateObject->setAttributes('x', $endX);
        $updateObject->setAttributes('y', $endY);
        $updateObject->setAttributes('zIndex', 100);
        foreach ($updateObject->get() as $data) {
            $drawCommands[] = $data;
        }

        $updateObject = new ObjectUpdate($elementUid . '_panel', $sessionId);
        $updateObject->setAttributes('x', $panelX);
        $updateObject->setAttributes('y', $panelY);
        $updateObject->setAttributes('zIndex', 100);
        foreach ($updateObject->get() as $data) {
            $drawCommands[] = $data;
        }

        $updateObject = new ObjectUpdate($elementUid . '_text_name', $sessionId);
        $updateObject->setAttributes('x', $panelX + 10);
        $updateObject->setAttributes('y', $panelY + 10);
        foreach ($updateObject->get() as $data) {
            $drawCommands[] = $data;
        }

        ObjectCache::flush($sessionId);
        $this->queueDrawCommands($player, $sessionId, $drawCommands);

        return true;
    }

    private function isTargetPanelRenderable(string $sessionId, string $targetUid): bool
    {
        $panel = ObjectCache::find($sessionId, $targetUid . '_panel');
        if (!is_array($panel)) {
            return true;
        }

        $attributes = $panel['attributes'] ?? null;
        if (!is_array($attributes)) {
            return true;
        }

        return (bool) ($attributes['renderable'] ?? true);
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
            putenv('DOCKER_HOST=tcp://127.0.0.1:2375');
            $docker = Docker::create();
            $docker->containerStop($container->container_id);
        } catch (\Throwable $e) {
            Log::warning('Unable to stop container for dead target', [
                'parent_type' => $parentType,
                'parent_id' => $parentId,
                'container_id' => $container->container_id,
                'error' => $e->getMessage(),
            ]);
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

            $requestId = Str::random(20);
            DrawRequest::query()->create([
                'session_id' => (string) $sessionId,
                'request_id' => $requestId,
                'player_id' => (int) ($payload['player_id'] ?? 0),
                'items' => json_encode($items),
            ]);

            $player = $payload['player'] ?? null;
            if ($player instanceof Player) {
                event(new DrawInterfaceEvent($player, $requestId));
            }
        }
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
}
