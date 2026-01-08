<?php

namespace App\Services;

class WebSocketService
{
    /**
     * Invia un messaggio via WebSocket e ritorna la risposta
     */
    public static function send($url, $payload)
    {
        // Parse dell'URL
        $urlParts = parse_url($url);
        $host = $urlParts['host'] ?? 'localhost';
        $port = $urlParts['port'] ?? 80;
        $path = $urlParts['path'] ?? '/';

        // Crea socket TCP
        $socket = @fsockopen($host, $port, $errno, $errstr, 5);

        if (!$socket) {
            return [
                'success' => false,
                'error' => "Impossibile connettersi: {$errstr} ({$errno})"
            ];
        }

        // Genera chiave WebSocket
        $key = base64_encode(random_bytes(16));

        // Invia handshake WebSocket
        $handshake = "GET {$path} HTTP/1.1\r\n";
        $handshake .= "Host: {$host}:{$port}\r\n";
        $handshake .= "Upgrade: websocket\r\n";
        $handshake .= "Connection: Upgrade\r\n";
        $handshake .= "Sec-WebSocket-Key: {$key}\r\n";
        $handshake .= "Sec-WebSocket-Version: 13\r\n";
        $handshake .= "\r\n";

        fwrite($socket, $handshake);

        // Leggi risposta handshake
        $response = '';
        while ($line = fgets($socket)) {
            $response .= $line;
            if (trim($line) === '') {
                break;
            }
        }

        if (strpos($response, '101 Switching Protocols') === false) {
            fclose($socket);
            return [
                'success' => false,
                'error' => 'WebSocket handshake fallito'
            ];
        }

        // Invia il messaggio
        $message = json_encode($payload);
        $frame = self::encodeFrame($message);
        fwrite($socket, $frame);

        // Leggi la risposta (con timeout)
        stream_set_timeout($socket, 5);
        $responseFrame = fread($socket, 8192);

        fclose($socket);

        if ($responseFrame) {
            $decoded = self::decodeFrame($responseFrame);
            return [
                'success' => true,
                'response' => json_decode($decoded, true) ?? $decoded
            ];
        }

        return [
            'success' => false,
            'error' => 'Nessuna risposta ricevuta'
        ];
    }

    /**
     * Codifica un messaggio in un frame WebSocket
     */
    private static function encodeFrame($message)
    {
        $length = strlen($message);
        $header = chr(0x81); // Text frame, FIN bit set

        if ($length <= 125) {
            $header .= chr($length | 0x80); // Mask bit set
        } elseif ($length <= 65535) {
            $header .= chr(126 | 0x80);
            $header .= pack('n', $length);
        } else {
            $header .= chr(127 | 0x80);
            $header .= pack('J', $length);
        }

        // Genera mask key
        $mask = pack('N', rand(1, 0x7FFFFFFF));
        $header .= $mask;

        // Applica mask al payload
        $masked = '';
        for ($i = 0; $i < $length; $i++) {
            $masked .= $message[$i] ^ $mask[$i % 4];
        }

        return $header . $masked;
    }

    /**
     * Decodifica un frame WebSocket
     */
    private static function decodeFrame($data)
    {
        if (strlen($data) < 2) {
            return '';
        }

        $length = ord($data[1]) & 127;
        $maskStart = 2;

        if ($length == 126) {
            $maskStart = 4;
            $length = unpack('n', substr($data, 2, 2))[1];
        } elseif ($length == 127) {
            $maskStart = 10;
            $length = unpack('J', substr($data, 2, 8))[1];
        }

        $decoded = '';
        for ($i = 0; $i < $length; $i++) {
            $decoded .= $data[$maskStart + $i] ?? '';
        }

        return $decoded;
    }
}
