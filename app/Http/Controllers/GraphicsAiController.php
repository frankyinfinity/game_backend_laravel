<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GraphicsAiController extends Controller
{
    public function models()
    {
        $token = config('services.openrouter.token');

        if (empty($token)) {
            return response()->json([
                'message' => 'OPENROUTER_API_KEY non configurata.',
            ], 422);
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get('https://openrouter.ai/api/v1/models');
        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'Impossibile connettersi a OpenRouter.',
            ], 503);
        }

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Errore durante il recupero dei modelli OpenRouter.',
                'details' => $response->json() ?: $response->body(),
            ], $response->status());
        }

        $models = collect($response->json('data', []))
            ->filter(fn ($model) => str_contains($model['id'] ?? '', ':free'))
            ->values()
            ->map(fn ($model) => [
                'id' => $model['id'] ?? null,
                'name' => $model['name'] ?? null,
            ]);

        return response()->json([
            'models' => $models,
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'prompt' => 'required|string|max:1000',
            'model_type' => 'nullable|string|max:100',
        ]);

        $token = config('services.openrouter.token');
        $model = config('services.openrouter.model');
        $endpoint = config('services.openrouter.endpoint');
        $referer = config('services.openrouter.referer');
        $title = config('services.openrouter.title');
        $maxTokens = (int) config('services.openrouter.max_tokens');
        $attempts = [];

        if (empty($token)) {
            return response()->json([
                'message' => 'OPENROUTER_API_KEY non configurata.',
            ], 422);
        }

        $systemPrompt = 'Sei un generatore di pixel art 32x32 per un editor canvas. Il prompt utente è scritto in italiano. Devi restituire solo un array JSON valido e minificato, senza oggetti wrapper, senza markdown e senza spiegazioni. La risposta deve essere direttamente un array di esattamente 32 stringhe, non un array di singoli caratteri. Ogni elemento dell array è una riga completa lunga esattamente 32 caratteri. Usa "." per i pixel vuoti trasparenti. Usa solo queste lettere per i colori: r,g,b,y,k,w,o,p. Significato colori: r rosso, g verde, b blu o azzurro, y giallo, k nero, w bianco, o arancione, p viola. Crea una piccola icona pixel art semplice, centrata e riconoscibile, con sfondo trasparente. Esempio formato corretto: ["................................","..............rr................"]. La risposta deve essere completa e JSON valido.';
        $payload = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role' => 'user',
                    'content' => 'Genera questa immagine: ' . $data['prompt'] . '. Restituisci solo l array JSON grezzo di 32 stringhe.',
                ],
            ],
            'temperature' => 0.2,
            'max_tokens' => $maxTokens,
        ];

        try {
            $client = Http::withToken($token)
                ->withHeaders([
                    'HTTP-Referer' => $referer,
                    'X-Title' => $title,
                ])
                ->timeout(90);

            $payload['model'] = $model;
            $response = $client->post($endpoint, $payload);

            if ($response->status() === 400) {
                $fallbackPayload = $payload;
                unset($fallbackPayload['response_format']);
                $response = $client->post($endpoint, $fallbackPayload);
            }

            $attempts[] = [
                'model' => $model,
                'status' => $response->status(),
                'message' => data_get($response->json(), 'error.message'),
            ];
        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'Impossibile connettersi a OpenRouter. Verifica connessione internet, DNS o proxy/firewall.',
                'endpoint' => $endpoint,
                'model' => $model,
            ], 503);
        }

        if (! $response->successful()) {
            if ($response->status() === 429) {
                $details = $response->json();
                $retryAfter = data_get($details, 'error.metadata.retry_after_seconds')
                    ?? $response->header('Retry-After');

                return response()->json([
                    'message' => $retryAfter
                        ? 'Tutti i modelli OpenRouter disponibili sono temporaneamente in rate limit. Riprova tra ' . $retryAfter . ' secondi.'
                        : 'Tutti i modelli OpenRouter disponibili sono temporaneamente in rate limit. Riprova tra poco.',
                    'details' => $details ?: $response->body(),
                    'attempts' => $attempts,
                ], 429);
            }

            return response()->json([
                'message' => 'Errore nella chiamata a OpenRouter.',
                'details' => $response->json() ?: $response->body(),
                'attempts' => $attempts,
            ], $response->status());
        }

        $generatedText = $this->extractGeneratedText($response->json());
        $payload = $this->extractJsonPayload($generatedText);
        $aiPixels = null;

        if (! $payload) {
            $payload = $this->extractTruncatedCompactPayload($generatedText);
        }

        if (! $payload) {
            $payload = $this->extractTruncatedCoordinatePixelsPayload($generatedText);
        }

        if (! $payload) {
            $payload = $this->extractTruncatedLetterRowsPayload($generatedText);
        }

        if ($payload && $this->isLetterRowPixelArray($payload)) {
            $aiPixels = $this->normalizeLetterRows($payload);
            $payload = [
                'pixels' => $this->expandLetterRows($aiPixels),
            ];
        }

        if (! $payload || ! isset($payload['pixels']) || ! $this->isValidPixelMatrix($payload['pixels'])) {
            return response()->json([
                'message' => 'La risposta AI non contiene pixel utilizzabili.',
                'raw' => $generatedText,
                'decoded' => $payload,
                'attempts' => $attempts,
            ], 422);
        }

        return response()->json([
            'ai_pixels' => $aiPixels,
            'pixels' => $payload['pixels'],
            'model' => $model,
            'attempts' => $attempts,
        ]);
    }

    private function extractGeneratedText(mixed $response): string
    {
        if (is_array($response) && isset($response[0]['generated_text'])) {
            return (string) $response[0]['generated_text'];
        }

        if (is_array($response) && isset($response['generated_text'])) {
            return (string) $response['generated_text'];
        }

        if (is_array($response) && isset($response['choices'][0]['message']['content'])) {
            return (string) $response['choices'][0]['message']['content'];
        }

        return is_string($response) ? $response : json_encode($response);
    }

    private function extractJsonPayload(string $text): ?array
    {
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $text = trim($text);
        $text = preg_replace('/^```(?:json)?/i', '', $text);
        $text = preg_replace('/```$/', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $arrayStart = strpos($text, '[');
        $arrayEnd = strrpos($text, ']');

        if ($arrayStart !== false && $arrayEnd !== false && $arrayEnd > $arrayStart) {
            $json = Str::substr($text, $arrayStart, $arrayEnd - $arrayStart + 1);
            $decoded = json_decode($json, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $json = Str::substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function extractTruncatedCompactPayload(string $text): ?array
    {
        preg_match_all('/\"([^\"]+)\"\\s*:\\s*\"(#[0-9A-Fa-f]{6}|[0-9A-Fa-f]{6})\"/', $text, $paletteMatches, PREG_SET_ORDER);
        preg_match_all('/\"([A-Za-z0-9\\.]{8,})\"/', $text, $rowMatches, PREG_SET_ORDER);

        $palette = [];

        foreach ($paletteMatches as $match) {
            $key = $match[1][0] ?? '';
            $color = $match[2];

            if ($key !== '' && $key !== '.') {
                $palette[$key] = str_starts_with($color, '#') ? $color : '#' . $color;
            }
        }

        $rows = [];

        foreach ($rowMatches as $match) {
            $value = $match[1];

            if (strlen($value) >= 16 && preg_match('/^[A-Za-z0-9\\.]+$/', $value) && ! preg_match('/^[0-9A-Fa-f]{6}$/', $value)) {
                $rows[] = $value;
            }
        }

        if (empty($palette) || empty($rows)) {
            return null;
        }

        return [
            'palette' => $palette,
            'rows' => $rows,
        ];
    }

    private function extractTruncatedCoordinatePixelsPayload(string $text): ?array
    {
        preg_match_all(
            '/\\{\\s*\"x\"\\s*:\\s*(\\d+)\\s*,\\s*\"y\"\\s*:\\s*(\\d+)\\s*,\\s*\"rgb\"\\s*:\\s*\"(#[0-9A-Fa-f]{6}|[0-9A-Fa-f]{6})\"\\s*\\}/',
            $text,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            return null;
        }

        $pixels = [];

        foreach ($matches as $match) {
            $pixels[] = [
                'x' => (int) $match[1],
                'y' => (int) $match[2],
                'rgb' => str_starts_with($match[3], '#') ? $match[3] : '#' . $match[3],
            ];
        }

        return [
            'pixels' => $pixels,
        ];
    }

    private function extractTruncatedLetterRowsPayload(string $text): ?array
    {
        preg_match_all('/\"([\\.rgbYykKwWoOpP]{8,})\"/', $text, $matches);

        if (empty($matches[1])) {
            return null;
        }

        return [
            'pixels' => $matches[1],
        ];
    }

    private function isValidPixelMatrix(mixed $pixels): bool
    {
        if (! is_array($pixels) || count($pixels) !== 32) {
            return false;
        }

        foreach ($pixels as $row) {
            if (! is_array($row) || count($row) !== 32) {
                return false;
            }

            foreach ($row as $color) {
                if ($color !== null && ! preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function normalizePixelMatrix(mixed $pixels): ?array
    {
        if (! is_array($pixels)) {
            return null;
        }

        $pixels = array_values($pixels);

        while (count($pixels) < 32) {
            $pixels[] = [];
        }

        $pixels = array_slice($pixels, 0, 32);
        $normalized = [];

        foreach ($pixels as $row) {
            if (! is_array($row)) {
                $row = [];
            }

            $row = array_values($row);

            while (count($row) < 32) {
                $row[] = null;
            }

            $row = array_slice($row, 0, 32);
            $normalizedRow = [];

            foreach ($row as $color) {
                $normalizedRow[] = $this->normalizePixelColor($color);
            }

            $normalized[] = $normalizedRow;
        }

        return $normalized;
    }

    private function normalizePixelsPayload(mixed $pixels): ?array
    {
        if (! is_array($pixels)) {
            return null;
        }

        if ($this->isLetterRowPixelArray($pixels)) {
            return $this->expandLetterRows($pixels);
        }

        $isCoordinateList = true;

        foreach ($pixels as $pixel) {
            if (! is_array($pixel) || ! array_key_exists('x', $pixel) || ! array_key_exists('y', $pixel)) {
                $isCoordinateList = false;
                break;
            }
        }

        if (! $isCoordinateList) {
            return $this->normalizePixelMatrix($pixels);
        }

        $matrix = array_fill(0, 32, array_fill(0, 32, null));

        foreach ($pixels as $pixel) {
            $x = (int) $pixel['x'];
            $y = (int) $pixel['y'];
            $rgb = $pixel['rgb'] ?? $pixel['color'] ?? $pixel['hex'] ?? null;
            $color = $this->normalizePixelColor($rgb);

            if ($x < 0 || $x > 31 || $y < 0 || $y > 31 || $color === null) {
                continue;
            }

            $matrix[$y][$x] = $color;
        }

        return $matrix;
    }

    private function isLetterRowPixelArray(array $pixels): bool
    {
        if (empty($pixels)) {
            return false;
        }

        if ($this->isFlatLetterPixelArray($pixels)) {
            return true;
        }

        foreach ($pixels as $row) {
            if (! is_string($row)) {
                return false;
            }
        }

        return true;
    }

    private function isFlatLetterPixelArray(array $pixels): bool
    {
        if (count($pixels) < 32) {
            return false;
        }

        foreach ($pixels as $pixel) {
            if (! is_string($pixel) || strlen($pixel) !== 1 || ! preg_match('/^[\\.rgbykwop]$/i', $pixel)) {
                return false;
            }
        }

        return true;
    }

    private function expandLetterRows(array $rows): array
    {
        $palette = [
            'r' => '#FF0000',
            'g' => '#00AA00',
            'b' => '#0000FF',
            'y' => '#FFFF00',
            'k' => '#111111',
            'w' => '#FFFFFF',
            'o' => '#FF8800',
            'p' => '#AA00FF',
        ];

        $rows = $this->normalizeLetterRows($rows);
        $matrix = [];

        foreach ($rows as $row) {
            $matrixRow = [];

            for ($i = 0; $i < 32; $i++) {
                $char = $row[$i];
                $matrixRow[] = $palette[$char] ?? null;
            }

            $matrix[] = $matrixRow;
        }

        return $matrix;
    }

    private function normalizeLetterRows(array $rows): array
    {
        if ($this->isFlatLetterPixelArray($rows)) {
            $flat = implode('', $rows);
            $flat = substr(str_pad($flat, 1024, '.'), 0, 1024);
            return str_split(strtolower($flat), 32);
        }

        $rows = array_values($rows);

        while (count($rows) < 32) {
            $rows[] = str_repeat('.', 32);
        }

        $rows = array_slice($rows, 0, 32);

        return array_map(
            fn ($row) => strtolower(substr(str_pad((string) $row, 32, '.'), 0, 32)),
            $rows
        );
    }

    private function normalizePixelColor(mixed $color): ?string
    {
        if ($color === null || $color === false) {
            return null;
        }

        if (is_array($color)) {
            if (isset($color['color'])) {
                $color = $color['color'];
            } elseif (isset($color['hex'])) {
                $color = $color['hex'];
            } else {
                return null;
            }
        }

        if (! is_string($color)) {
            return null;
        }

        $color = trim($color);

        if ($color === '' || in_array(strtolower($color), ['null', 'none', 'transparent', 'clear'], true)) {
            return null;
        }

        if (preg_match('/^[0-9A-Fa-f]{6}$/', $color)) {
            return '#' . strtoupper($color);
        }

        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return strtoupper($color);
        }

        if (preg_match('/^#[0-9A-Fa-f]{3}$/', $color)) {
            return '#' . strtoupper($color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3]);
        }

        if (preg_match('/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)(?:,\\s*([0-9.]+))?\\)/i', $color, $matches)) {
            $alpha = isset($matches[4]) ? (float) $matches[4] : 1;

            if ($alpha <= 0) {
                return null;
            }

            return sprintf(
                '#%02X%02X%02X',
                max(0, min(255, (int) $matches[1])),
                max(0, min(255, (int) $matches[2])),
                max(0, min(255, (int) $matches[3]))
            );
        }

        return null;
    }

    private function expandCompactPixelPayload(mixed $palette, mixed $rows): ?array
    {
        if (! is_array($palette) || ! is_array($rows)) {
            return null;
        }

        $palette = $this->normalizePalette($palette);

        if ($palette === null) {
            return null;
        }

        $rows = array_values($rows);

        while (count($rows) < 32) {
            $rows[] = str_repeat('.', 32);
        }

        $rows = array_slice($rows, 0, 32);

        foreach ($palette as $key => $color) {
            if (! is_string($key) || strlen($key) !== 1 || ! is_string($color) || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                return null;
            }
        }

        $pixels = [];

        foreach ($rows as $row) {
            if (! is_string($row)) {
                return null;
            }

            $row = substr(str_pad($row, 32, '.'), 0, 32);
            $pixelRow = [];

            for ($i = 0; $i < 32; $i++) {
                $char = $row[$i];

                if ($char === '.') {
                    $pixelRow[] = null;
                    continue;
                }

                if (! array_key_exists($char, $palette)) {
                    return null;
                }

                $pixelRow[] = $palette[$char];
            }

            $pixels[] = $pixelRow;
        }

        return $pixels;
    }

    private function normalizePalette(array $palette): ?array
    {
        $normalized = [];

        foreach ($palette as $key => $color) {
            if (is_array($color) && isset($color['color'])) {
                $color = $color['color'];
            }

            if (! is_string($color)) {
                return null;
            }

            $color = strtoupper(trim($color));

            if (preg_match('/^[0-9A-F]{6}$/', $color)) {
                $color = '#' . $color;
            }

            if (! preg_match('/^#[0-9A-F]{6}$/', $color)) {
                return null;
            }

            $key = (string) $key;
            $key = $key[0] ?? '';

            if ($key === '' || $key === '.') {
                continue;
            }

            $normalized[$key] = $color;
        }

        return empty($normalized) ? null : $normalized;
    }

    private function extractPixelMatrixFromRows(array $payload): ?array
    {
        if (! isset($payload['rows']) || ! is_array($payload['rows'])) {
            return null;
        }

        $palette = isset($payload['palette']) && is_array($payload['palette'])
            ? $this->normalizePalette($payload['palette'])
            : null;

        if ($palette === null) {
            $palette = [
                'A' => '#000000',
                'B' => '#FFFFFF',
                'C' => '#FF0000',
                'D' => '#00AA00',
                'E' => '#0000FF',
                'F' => '#FFFF00',
                'G' => '#AA00FF',
                'H' => '#FF8800',
            ];
        }

        return $this->expandCompactPixelPayload($palette, $payload['rows']);
    }
}
