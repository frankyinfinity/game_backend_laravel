<?php

namespace App\Console\Commands;

use App\Models\Container;
use App\Models\Player;
use Illuminate\Console\Command;

class TestTilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:tiles
                            {player_id=73 : Player ID}
                            {--tile_i= : Filter by tile_i}
                            {--tile_j= : Filter by tile_j}
                            {--ws_host=127.0.0.1 : Host where map websocket is exposed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test command to query map websocket get_tile_info for a player';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $playerId = (int) $this->argument('player_id');
        $player = Player::query()->find($playerId);

        if (!$player) {
            $this->error("Player {$playerId} non trovato.");
            return self::FAILURE;
        }

        if (empty($player->birth_region_id)) {
            $this->error("Player {$playerId} non ha birth_region_id.");
            return self::FAILURE;
        }

        $tileI = $this->option('tile_i');
        $tileJ = $this->option('tile_j');
        if ($tileI === null || $tileJ === null) {
            $this->error('Devi passare sia --tile_i che --tile_j per interrogare il websocket map.');
            return self::FAILURE;
        }

        $mapContainer = Container::query()
            ->where('parent_type', Container::PARENT_TYPE_MAP)
            ->where('parent_id', (int) $player->birth_region_id)
            ->whereNotNull('ws_port')
            ->orderByDesc('id')
            ->first();

        if (!$mapContainer) {
            $this->error("Container map non trovato (o ws_port nullo) per birth_region_id {$player->birth_region_id}.");
            return self::FAILURE;
        }

        $host = (string) $this->option('ws_host');
        $port = (int) $mapContainer->ws_port;

        $this->info("Player: {$playerId}");
        $this->info("Birth region: {$player->birth_region_id}");
        $this->info("Map websocket: ws://{$host}:{$port}");

        try {
            [$welcome, $reply] = $this->queryTileInfoViaWebSocket(
                $host,
                $port,
                (int) $tileI,
                (int) $tileJ
            );

            if ($welcome !== null) {
                $this->line('Welcome:');
                $this->line(json_encode($welcome, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            $this->line('Reply:');
            $this->line(json_encode($reply, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            $this->error('Errore websocket: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function queryTileInfoViaWebSocket(string $host, int $port, int $tileI, int $tileJ): array
    {
        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            5
        );

        if ($socket === false) {
            throw new \RuntimeException("Connessione fallita ({$errno}): {$errstr}");
        }

        stream_set_timeout($socket, 5);

        try {
            $this->performWebSocketHandshake($socket, $host, $port);

            $welcomeRaw = $this->readWebSocketFrame($socket);
            $welcome = $welcomeRaw !== null ? json_decode($welcomeRaw, true) : null;

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
                throw new \RuntimeException('Nessuna risposta dal websocket.');
            }

            $reply = json_decode($replyRaw, true);
            if (!is_array($reply)) {
                throw new \RuntimeException('Risposta websocket non valida (JSON).');
            }

            return [$welcome, $reply];
        } finally {
            fclose($socket);
        }
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
        $frame = chr(0x81); // FIN + text frame

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

        // Close frame
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
