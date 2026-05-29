<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GraphicsAiController extends Controller
{
    public function models()
    {
        return response()->json([
            'models' => [
                ['id' => 'black-forest-labs/flux-dev', 'name' => 'Flux Dev (economico)'],
            ],
        ]);
    }

    public function generate(Request $request)
    {
        set_time_limit(120);

        $data = $request->validate([
            'prompt' => 'required|string|max:500',
            'model_type' => 'nullable|string|max:100',
        ]);

        $token = config('services.openrouter.token');
        $endpoint = config('services.openrouter.endpoint', 'https://openrouter.ai/api/v1/images/generations');
        $model = config('services.openrouter.model', 'black-forest-labs/flux-dev');

        if (empty($token)) {
            return response()->json(['message' => 'API key non configurata.'], 422);
        }

        $payload = [
            'model' => $model,
            'prompt' => "pixel art 32x32, " . $data['prompt'] . ", flat colors, no shading, transparent background",
            'n' => 1,
            'size' => '256x256',
        ];

        try {
            $response = Http::withToken($token)
                ->timeout(60)
                ->post($endpoint, $payload);
        } catch (ConnectionException $e) {
            return response()->json(['message' => 'Impossibile connettersi a OpenRouter.'], 503);
        }

        if (!$response->successful()) {
            $details = $response->json() ?: $response->body();
            return response()->json([
                'message' => 'Errore generazione immagine.',
                'details' => $details,
            ], $response->status());
        }

        $body = $response->json();
        $imageUrl = data_get($body, 'data.0.url');

        if (!$imageUrl) {
            return response()->json([
                'message' => 'Nessuna immagine generata.',
                'details' => $body,
            ], 422);
        }

        // Scarica immagine
        try {
            $imgData = Http::timeout(30)->get($imageUrl)->body();
        } catch (ConnectionException $e) {
            return response()->json(['message' => 'Impossibile scaricare l\'immagine generata.'], 502);
        }

        if (empty($imgData)) {
            return response()->json(['message' => 'Immagine vuota.'], 502);
        }

        // Carica con GD e ridimensiona a 32x32
        $srcImg = @imagecreatefromstring($imgData);
        if (!$srcImg) {
            return response()->json(['message' => 'Impossibile processare l\'immagine.'], 422);
        }

        $srcW = imagesx($srcImg);
        $srcH = imagesy($srcImg);

        $pixelImg = imagecreatetruecolor(32, 32);
        imagealphablending($pixelImg, false);
        imagesavealpha($pixelImg, true);
        $transparent = imagecolorallocatealpha($pixelImg, 0, 0, 0, 127);
        imagefill($pixelImg, 0, 0, $transparent);
        imagecopyresampled($pixelImg, $srcImg, 0, 0, 0, 0, 32, 32, $srcW, $srcH);
        imagedestroy($srcImg);

        // Estrai pixel
        $pixels = [];
        for ($y = 0; $y < 32; $y++) {
            $row = [];
            for ($x = 0; $x < 32; $x++) {
                $rgb = imagecolorat($pixelImg, $x, $y);
                $alpha = ($rgb >> 24) & 0x7F;
                if ($alpha >= 100) {
                    $row[] = null;
                } else {
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $row[] = sprintf('#%02X%02X%02X', $r, $g, $b);
                }
            }
            $pixels[] = $row;
        }
        imagedestroy($pixelImg);

        return response()->json([
            'pixels' => $pixels,
            'model' => 'black-forest-labs/flux-dev',
        ]);
    }
}