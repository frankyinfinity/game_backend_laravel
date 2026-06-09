<?php

namespace App\Services;

use App\Models\Container;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service to probe the actual reachability of each container's WebSocket port.
 *
 * Strategy: open a short TCP connection to the container's ws_port on the
 * configured docker host. This mirrors what the browser would do after the
 * ws-gateway routed the connection, but checks the underlying port reachability
 * which is the most common failure point.
 *
 * For containers that have no ws_port (Objective, CacheSync, ChimicalElement)
 * the service returns a special status "no_ws" so the UI can render a neutral
 * state instead of a red "down" indicator.
 */
class WebSocketHealthService
{
    public function __construct(private readonly DockerContainerService $docker)
    {
    }

    /**
     * Probe the given containers and return an array keyed by container id:
     *   [id => [
     *       'id'           => int,
     *       'name'         => string,
     *       'parent_type'  => string,
     *       'ws_port'      => int|null,
     *       'status'       => 'online'|'offline'|'no_ws',
     *       'response_ms'  => int|null,
     *       'error'        => string|null,
     *       'checked_at'   => string (ISO),
     *   ]]
     *
     * @param  iterable<Container>  $containers
     * @return array<int, array<string, mixed>>
     */
    public function probeMany(iterable $containers, bool $useCache = true, int $cacheTtlSeconds = 2): array
    {
        $host = (string) config('remote_docker.docker_host_ip');
        $result = [];

        foreach ($containers as $container) {
            /** @var Container $container */
            $id = (int) $container->id;
            $port = $container->ws_port ? (int) $container->ws_port : null;

            if (!$port) {
                $result[$id] = $this->emptyEntry($container, 'no_ws', null, null, 'Container senza porta WS');
                continue;
            }

            $cacheKey = sprintf('wshealth:%s:%d', $host, $port);
            if ($useCache && Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                // Re-attach container-specific fields
                $cached['id'] = $id;
                $cached['name'] = (string) $container->name;
                $cached['parent_type'] = (string) $container->parent_type;
                $result[$id] = $cached;
                continue;
            }

            $entry = $this->probeSingle($host, $port, $container);

            if ($useCache) {
                Cache::put($cacheKey, $entry, $cacheTtlSeconds);
            }

            $result[$id] = $entry;
        }

        return $result;
    }

    /**
     * Probe a single host:port. Returns the same entry shape used by probeMany().
     */
    public function probeSingle(string $host, int $port, ?Container $container = null): array
    {
        $start = microtime(true);
        $error = null;
        $status = 'offline';
        $responseMs = null;

        // Cheap first: TCP connect with a short timeout. If it succeeds, the WS
        // server is at least listening; the actual WS handshake is the
        // browser/gateway's responsibility.
        $connection = @fsockopen($host, $port, $errno, $errstr, 0.6);

        if ($connection === false) {
            $error = $errstr ?: ('Errore ' . $errno);
            $status = 'offline';
        } else {
            // Try a real WebSocket handshake so we know it really is a WS server.
            $handshake = $this->performWebSocketHandshake($connection, $host, $port);
            fclose($connection);

            if ($handshake['ok']) {
                $status = 'online';
            } else {
                $status = 'offline';
                $error = $handshake['error'] ?? 'WS handshake fallito';
            }
        }

        $responseMs = (int) round((microtime(true) - $start) * 1000);

        return $this->emptyEntry(
            $container,
            $status,
            $responseMs,
            $error,
            $status === 'online' ? null : ($error ?? 'non raggiungibile')
        );
    }

    /**
     * Try a minimal RFC6455 opening handshake against an already open socket.
     *
     * @return array{ok: bool, error: ?string}
     */
    private function performWebSocketHandshake($socket, string $host, int $port): array
    {
        $key = base64_encode(random_bytes(16));
        $path = '/';
        $req = "GET {$path} HTTP/1.1\r\n"
            . "Host: {$host}:{$port}\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Key: {$key}\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "\r\n";

        @stream_set_timeout($socket, 1);
        $written = @fwrite($socket, $req);
        if ($written === false) {
            return ['ok' => false, 'error' => 'Impossibile scrivere handshake'];
        }

        $response = '';
        $deadline = microtime(true) + 1.0;
        while (microtime(true) < $deadline) {
            $chunk = @fread($socket, 2048);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $response .= $chunk;
            if (str_contains($response, "\r\n\r\n")) {
                break;
            }
        }

        if ($response === '') {
            return ['ok' => false, 'error' => 'Nessuna risposta (timeout)'];
        }

        $statusLine = strtok($response, "\r\n");
        if ($statusLine === false) {
            return ['ok' => false, 'error' => 'Risposta non valida'];
        }

        // Acceptable: 101 Switching Protocols (the WS server) or
        // any 4xx/5xx that still indicates an HTTP-speaking server.
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $statusLine, $m)) {
            $code = (int) $m[1];
            if ($code === 101) {
                return ['ok' => true, 'error' => null];
            }
            // Many WS servers reject the probe path with 400/404; that's still
            // a sign the port is alive and WS-aware.
            if ($code >= 400 && $code < 500) {
                return ['ok' => true, 'error' => null];
            }
            return ['ok' => false, 'error' => 'HTTP ' . $code];
        }

        return ['ok' => false, 'error' => 'Status line non riconosciuta'];
    }

    private function emptyEntry(?Container $container, string $status, ?int $responseMs, ?string $error, ?string $message): array
    {
        return [
            'id' => $container ? (int) $container->id : null,
            'name' => $container ? (string) $container->name : null,
            'parent_type' => $container ? (string) $container->parent_type : null,
            'ws_port' => $container && $container->ws_port ? (int) $container->ws_port : null,
            'status' => $status,
            'response_ms' => $responseMs,
            'error' => $error,
            'message' => $message,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
