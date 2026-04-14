<?php

namespace App\Jobs;

use App\Models\BirthRegion;
use App\Models\BirthRegionDetail;
use App\Models\BirthRegionDetailData;
use App\Models\Player;
use App\Models\PlayerValue;
use App\Models\Entity;
use App\Models\Container;
use App\Models\EntityChimicalElement;
use App\Models\PlayerRuleChimicalElement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConsumeChimicalElementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $birthRegionId;

    public function __construct(int $birthRegionId)
    {
        $this->birthRegionId = $birthRegionId;
    }

    public function handle(): void
    {
        $birthRegionId = $this->birthRegionId;

        $birthRegion = BirthRegion::find($birthRegionId);
        if ($birthRegion === null) {
            Log::warning('[ConsumeChimicalElementJob] Birth region non trovata', [
                'birth_region_id' => $birthRegionId,
            ]);
            return;
        }

        $player = Player::query()->where('birth_region_id', $birthRegionId)->first();
        if ($player === null) {
            Log::warning('[ConsumeChimicalElementJob] Player non trovato', [
                'birth_region_id' => $birthRegionId,
            ]);
            return;
        }

        $playerId = $player->id;

        if (PlayerValue::hasAnyActive($playerId, [PlayerValue::KEY_CHIMICAL_ELEMENT_CONSUME])) {
            Log::info('[ConsumeChimicalElementJob] Job già in esecuzione per player ' . $playerId);
            return;
        }

        PlayerValue::setFlag($playerId, PlayerValue::KEY_CHIMICAL_ELEMENT_CONSUME, true);

        try {
            $this->processConsumeChimicalElement($birthRegion, $player);
        } finally {
            PlayerValue::setFlag($playerId, PlayerValue::KEY_CHIMICAL_ELEMENT_CONSUME, false);
        }
    }

    private function processConsumeChimicalElement(BirthRegion $birthRegion, Player $player): void
    {
        $birthRegionId = $birthRegion->id;
        $playerId = $player->id;

        Log::info('[ConsumeChimicalElementJob] Chiamata ricevuta', [
            'birth_region_id' => $birthRegionId,
        ]);

        $host = config('remote_docker.docker_host_ip');
        $gatewayPort = config('remote_docker.websocket_gateway_port', 9001);

        $entities = Entity::whereHas('specie', fn($q) => $q->where('player_id', $playerId))->get();
        Log::info('[ConsumeChimicalElementJob] found ' . $entities->count() . ' entities for player ' . $playerId);

        $playerRules = PlayerRuleChimicalElement::query()
            ->where('player_id', $playerId)
            ->get()
            ->keyBy(function($rule) {
                return $rule->chimical_element_id ? 'chimical_element_' . $rule->chimical_element_id : 'complex_' . $rule->complex_chimical_element_id;
            });

        foreach ($entities as $entity) {
            Log::info('[ConsumeChimicalElementJob] entity ' . $entity->uid . ' at position (' . $entity->tile_i . ', ' . $entity->tile_j . ')');

            try {
                $mapContainer = Container::query()
                    ->where('parent_type', Container::PARENT_TYPE_MAP)
                    ->where('parent_id', $birthRegionId)
                    ->whereNotNull('ws_port')
                    ->first();
                if ($mapContainer === null || !$mapContainer->ws_port) {
                    Log::info('[ConsumeChimicalElementJob] map container not found for birth_region ' . $birthRegionId);
                    continue;
                }

                $socket = $this->openMapWebSocket($host, (int) $mapContainer->ws_port);
                try {
                    $this->readWebSocketFrame($socket);
                    $reply = $this->queryTileDetails($socket, (int) $entity->tile_i, (int) $entity->tile_j);
                    
                    $tileElements = [];
                    if (isset($reply['detail']['birth_region_detail_data']) && is_array($reply['detail']['birth_region_detail_data'])) {
                        foreach ($reply['detail']['birth_region_detail_data'] as $data) {
                            $chimicalEl = null;
                            $complexEl = null;
                            
                            if (!empty($data['json_chimical_element'])) {
                                $chimicalEl = is_string($data['json_chimical_element']) 
                                    ? json_decode($data['json_chimical_element'], true) 
                                    : $data['json_chimical_element'];
                            }
                            if (!empty($data['json_complex_chimical_element'])) {
                                $complexEl = is_string($data['json_complex_chimical_element']) 
                                    ? json_decode($data['json_complex_chimical_element'], true) 
                                    : $data['json_complex_chimical_element'];
                            }
                            
                            if ($chimicalEl) {
                                $tileElements[] = [
                                    'id' => $chimicalEl['id'] ?? null,
                                    'name' => $chimicalEl['name'] ?? '',
                                    'value' => $data['quantity'] ?? 0,
                                    'type' => 'chimical_element',
                                    'detail_data_id' => $data['id']
                                ];
                            }
                            if ($complexEl) {
                                $tileElements[] = [
                                    'id' => $complexEl['id'] ?? null,
                                    'name' => $complexEl['name'] ?? '',
                                    'value' => $data['quantity'] ?? 0,
                                    'type' => 'complex_chimical_element',
                                    'detail_data_id' => $data['id']
                                ];
                            }
                        }
                    }
                    
                    Log::info('[ConsumeChimicalElementJob] tile elements for entity ' . $entity->uid . ': ' . json_encode($tileElements));
                    
                    $validElements = [];
                    foreach ($tileElements as $element) {
                        $ruleKey = $element['type'] === 'chimical_element' 
                            ? 'chimical_element_' . $element['id'] 
                            : 'complex_' . $element['id'];
                        
                        $rule = $playerRules->get($ruleKey);
                        if (!$rule) {
                            continue;
                        }
                        
                        $entityChimical = EntityChimicalElement::query()
                            ->where('entity_id', $entity->id)
                            ->where('player_rule_chimical_element_id', $rule->id)
                            ->first();
                        
                        if (!$entityChimical) {
                            continue;
                        }
                        
                        $maxValue = (int) $rule->max;
                        if ($entityChimical->value < $maxValue) {
                            $validElements[] = array_merge($element, [
                                'player_rule_chimical_element_id' => $rule->id,
                                'entity_chimical_element_id' => $entityChimical->id
                            ]);
                        }
                    }
                    
                    $maxConsume = PlayerValue::getIntegerValue($playerId, PlayerValue::KEY_CHIMICAL_ELEMENT_CONSUME_PER_TICK);
                    if (empty($validElements) || $maxConsume <= 0) {
                        Log::info('[ConsumeChimicalElementJob] no valid elements or maxConsume=0 for entity ' . $entity->uid);
                        continue;
                    }
                    
                    $groupedByElement = [];
                    foreach ($validElements as $el) {
                        $key = $el['type'] . '_' . $el['id'];
                        if (!isset($groupedByElement[$key])) {
                            $groupedByElement[$key] = [
                                'name' => $el['name'],
                                'type' => $el['type'],
                                'available' => $el['value'],
                                'rule_id' => $el['player_rule_chimical_element_id'],
                                'entity_chimical_id' => $el['entity_chimical_element_id'],
                                'detail_data_id' => $el['detail_data_id']
                            ];
                        }
                    }
                    
                    $toConsume = [];
                    $remainingSlots = $maxConsume;
                    $elementKeys = array_keys($groupedByElement);
                    shuffle($elementKeys);
                    
                    while ($remainingSlots > 0 && !empty($elementKeys)) {
                        $key = array_shift($elementKeys);
                        $el = $groupedByElement[$key];
                        
                        $consumeNow = rand(0, 1);
                        if ($consumeNow && $el['available'] > 0) {
                            $toConsume[] = $el;
                            $el['available']--;
                            $groupedByElement[$key]['available'] = $el['available'];
                            $remainingSlots--;
                        }
                        
                        if ($el['available'] > 0 && !in_array($key, $elementKeys)) {
                            $elementKeys[] = $key;
                            shuffle($elementKeys);
                        }
                    }
                    
                    Log::info('[ConsumeChimicalElementJob] consuming ' . count($toConsume) . ' elements for entity ' . $entity->uid . ': ' . json_encode(array_column($toConsume, 'name')));
                    
                    foreach ($toConsume as $element) {
                        $detailData = BirthRegionDetailData::find($element['detail_data_id']);
                        if ($detailData && $detailData->quantity > 0) {
                            $detailData->quantity--;
                            if ($detailData->quantity <= 0) {
                                $detailData->delete();
                            } else {
                                $detailData->save();
                            }
                            
                            $entityChimical = EntityChimicalElement::find($element['entity_chimical_id']);
                            $rule = PlayerRuleChimicalElement::find($element['rule_id']);
                            if ($entityChimical && $rule) {
                                $entityChimical->value += 1;
                                $maxValue = (int) $rule->max;
                                if ($entityChimical->value > $maxValue) {
                                    $entityChimical->value = $maxValue;
                                }
                                $entityChimical->save();
                                Log::info('[ConsumeChimicalElementJob] consumed element ' . $element['name'] . ' (type: ' . $element['type'] . ') for entity ' . $entity->uid);
                            }
                        }
                    }
                } finally {
                    fclose($socket);
                }
            } catch (\Throwable $e) {
                Log::info('[ConsumeChimicalElementJob] error for entity ' . $entity->uid . ': ' . $e->getMessage());
            }
        }
    }

    private function openMapWebSocket(string $host, int $targetPort)
    {
        $gatewayPort = (int) config('remote_docker.websocket_gateway_port', 9001);
        $socket = @stream_socket_client(
            "tcp://{$host}:{$gatewayPort}",
            $errno,
            $errstr,
            5
        );
        if ($socket === false) {
            throw new \RuntimeException("Connessione websocket al gateway fallita ({$errno}): {$errstr}");
        }
        stream_set_timeout($socket, 5);
        $this->performWebSocketHandshake($socket, $host, $gatewayPort, $targetPort);
        return $socket;
    }

    private function queryTileDetails($socket, int $tileI, int $tileJ): array
    {
        $payload = [
            'command' => 'get_birth_region_details',
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
            return $decoded;
        }
        return $payload;
    }

    private function readExactBytes($socket, int $length): ?string
    {
        $buffer = '';
        $remaining = $length;
        while ($remaining > 0) {
            $chunk = fread($socket, $remaining);
            if ($chunk === false || $chunk === '') {
                return null;
            }
            $buffer .= $chunk;
            $remaining -= strlen($chunk);
        }
        return $buffer;
    }
}
