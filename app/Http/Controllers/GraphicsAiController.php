<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GraphicsAiController extends Controller
{
    public function models()
    {
        return response()->json([
            'models' => [
                ['id' => 'local-stable-diffusion', 'name' => 'Stable Diffusion (locale, gratuito)'],
            ],
        ]);
    }

    public function generate(Request $request)
    {
        set_time_limit(600);

        $data = $request->validate([
            'prompt' => 'required|string|max:500',
        ]);

        return $this->generateLocal($data['prompt']);
    }

    private function generateLocal(string $prompt)
    {
        $scriptPath = base_path('scripts/local_pixel_gen.py');

        if (!file_exists($scriptPath)) {
            return response()->json(['message' => 'Script locale non trovato.'], 500);
        }

        $escapedPrompt = escapeshellarg($prompt);
        $cmd = "python {$scriptPath} {$escapedPrompt} 2>nul";

        exec($cmd, $output, $returnCode);

        $jsonOutput = implode("\n", $output);

        if ($returnCode !== 0) {
            return response()->json([
                'message' => 'Errore generazione locale.',
                'details' => $jsonOutput,
            ], 500);
        }

        $result = json_decode($jsonOutput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'message' => 'Output non valido dallo script locale.',
                'details' => $jsonOutput,
            ], 500);
        }

        if (isset($result['error'])) {
            return response()->json([
                'message' => 'Errore generazione locale.',
                'details' => $result['error'],
            ], 500);
        }

        return response()->json([
            'pixels' => $result['pixels'],
            'model' => $result['model'] ?? 'local-stable-diffusion',
        ]);
    }
}