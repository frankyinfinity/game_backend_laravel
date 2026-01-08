#!/usr/bin/env php
<?php

/**
 * Script di test rapido per WebSocket Entity
 * 
 * Uso: php test-websocket.php {porta} {comando}
 * Esempio: php test-websocket.php 9001 get_position
 */

if ($argc < 3) {
    echo "Uso: php test-websocket.php {porta} {comando} [action]\n";
    echo "Esempi:\n";
    echo "  php test-websocket.php 9001 get_position\n";
    echo "  php test-websocket.php 9001 move up\n";
    exit(1);
}

$port = $argv[1];
$command = $argv[2];
$action = $argv[3] ?? null;

$url = "ws://localhost:{$port}";

echo "🔌 Connessione a {$url}...\n";

// Prepara payload
$payload = ['command' => $command];
if ($action) {
    $payload['params'] = ['action' => $action];
}

// Connessione WebSocket semplice
$host = 'localhost';
$socket = @fsockopen($host, $port, $errno, $errstr, 5);

if (!$socket) {
    echo "❌ Errore: Impossibile connettersi - {$errstr} ({$errno})\n";
    exit(1);
}

// WebSocket handshake
$key = base64_encode(random_bytes(16));
$handshake = "GET / HTTP/1.1\r\n";
$handshake .= "Host: {$host}:{$port}\r\n";
$handshake .= "Upgrade: websocket\r\n";
$handshake .= "Connection: Upgrade\r\n";
$handshake .= "Sec-WebSocket-Key: {$key}\r\n";
$handshake .= "Sec-WebSocket-Version: 13\r\n";
$handshake .= "\r\n";

fwrite($socket, $handshake);

// Leggi handshake response
$response = '';
while ($line = fgets($socket)) {
    $response .= $line;
    if (trim($line) === '') {
        break;
    }
}

if (strpos($response, '101 Switching Protocols') === false) {
    echo "❌ Handshake fallito\n";
    fclose($socket);
    exit(1);
}

echo "✅ Connesso!\n";

// Leggi messaggio di benvenuto
stream_set_timeout($socket, 2);
$welcomeFrame = fread($socket, 8192);
if ($welcomeFrame) {
    $welcome = decodeFrame($welcomeFrame);
    echo "📨 Messaggio di benvenuto: {$welcome}\n";
}

// Invia comando
echo "📤 Invio comando: " . json_encode($payload) . "\n";
$message = json_encode($payload);
$frame = encodeFrame($message);
fwrite($socket, $frame);

// Leggi risposta
stream_set_timeout($socket, 5);
$responseFrame = fread($socket, 8192);

if ($responseFrame) {
    $decoded = decodeFrame($responseFrame);
    echo "📥 Risposta:\n";
    $json = json_decode($decoded, true);
    if ($json) {
        echo json_encode($json, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $decoded . "\n";
    }
} else {
    echo "⚠️  Nessuna risposta ricevuta\n";
}

fclose($socket);
echo "✅ Disconnesso\n";

// === Funzioni Helper ===

function encodeFrame($message) {
    $length = strlen($message);
    $header = chr(0x81); // Text frame, FIN bit

    if ($length <= 125) {
        $header .= chr($length | 0x80);
    } elseif ($length <= 65535) {
        $header .= chr(126 | 0x80);
        $header .= pack('n', $length);
    } else {
        $header .= chr(127 | 0x80);
        $header .= pack('J', $length);
    }

    // Mask
    $mask = pack('N', rand(1, 0x7FFFFFFF));
    $header .= $mask;

    $masked = '';
    for ($i = 0; $i < $length; $i++) {
        $masked .= $message[$i] ^ $mask[$i % 4];
    }

    return $header . $masked;
}

function decodeFrame($data) {
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
