<?php

namespace App\Console\Commands;

use App\Models\ElementHasPosition;
use App\Models\ElementHasPositionNeuron;
use App\Models\Container;
use App\Models\Entity;
use App\Models\Neuron;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestBrainCommand extends Command
{
    private array $processedNeuronsById = [];

    protected $signature = 'test:brain {element_has_position_id=7753} {--ws_host=127.0.0.1 : Host websocket mappa}';

    protected $description = 'Test ElementHasPosition::find(7753)';

    public function handle(): int
    {
        $elementHasPositionId = (int) $this->argument('element_has_position_id');
        $item = ElementHasPosition::query()
            ->with([
                'brain.neurons.outgoingLinks',
                'brain.neurons.incomingLinks',
            ])
            ->find($elementHasPositionId);

        if ($item === null) {
            $this->error("ElementHasPosition {$elementHasPositionId} non trovato.");
            return self::FAILURE;
        }

        $orderedFlow = $this->buildOrderedNeuronsWithFromLink($item);
        $this->processedNeuronsById = [];
        foreach ($orderedFlow as &$orderedNeuron) {
            $this->handleNeuronByType($orderedNeuron);
            if (isset($orderedNeuron['id'])) {
                $this->processedNeuronsById[(int) $orderedNeuron['id']] = $orderedNeuron;
            }
        }
        unset($orderedNeuron);

        $this->line(json_encode($orderedFlow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
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

    private function handleNeuronByType(array &$neuron): void
    {
        switch ($neuron['type'] ?? null) {
            case Neuron::TYPE_DETECTION:
                $this->handleDetectionNeuron($neuron);
                break;
            case Neuron::TYPE_PATH:
                $this->handlePathNeuron($neuron);
                break;
            default:
                $this->handleUnknownNeuron($neuron);
                break;
        }
    }

    private function handleDetectionNeuron(array &$neuron): void
    {
        $elementHasPositionId = (int) $this->argument('element_has_position_id');
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

    private function handlePathNeuron(array $neuron): void
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

        // Placeholder: path logic will use $fromDetectionResult.
        Log::info('path');
        $strCoordinate = str_replace('(', '', $fromDetectionResult);
        $strCoordinate = str_replace(')', '', $strCoordinate);
        $coordinates = explode(',', $strCoordinate);
        $tileI = $coordinates[0];
        $tileJ = $coordinates[1];
        Log::info($tileI);
        Log::info($tileJ);

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

        $host = (string) $this->option('ws_host');
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
}
