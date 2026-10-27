<?php

namespace App\Http\Controllers;

use App\Models\Container as DockerContainer;
use App\Models\Player;
use App\Services\DockerContainerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class TestController extends Controller
{
    public function index()
    {
        $players = Player::with(['user', 'birthRegion'])
            ->orderByDesc('created_at')
            ->get();

        $favoritePlayerId = null;
        $user = auth()->user();
        if ($user) {
            $favoritePlayerId = $user->favorite_player_id;
        }

        return view('test', compact('players', 'favoritePlayerId'));
    }

    public function action(Request $request)
    {
        // Handle the form data
        $data = $request->all();
        \Log::info('Form data received:', $data);
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function console()
    {
        return view('test_console');
    }

    public function consoleExec(Request $request)
    {
        $command = trim((string) $request->input('command', ''));

        if ($command === '') {
            return response()->json([
                'success' => false,
                'output' => 'Nessun comando specificato.',
            ]);
        }

        // Build the gcloud SSH command to the remote instance
        $fullCommand = sprintf(
            'gcloud compute ssh --zone %s %s --project %s --command %s',
            escapeshellarg(config('remote_docker.gcloud_zone')),
            escapeshellarg(config('remote_docker.gcloud_instance')),
            escapeshellarg(config('remote_docker.gcloud_project')),
            escapeshellarg($command)
        );

        $process = Process::fromShellCommandline($fullCommand);
        $process->setTimeout(300);
        $env = getenv();
        if (is_array($env)) {
            $env['CLOUDSDK_PYTHON'] = 'C:\\Python314\\python.exe';
            $process->setEnv($env);
        }

        try {
            $process->run();
            $output = $process->getOutput() . $process->getErrorOutput();
            $success = $process->isSuccessful();

            return response()->json([
                'success' => $success,
                'output' => trim($output),
                'exit_code' => $process->getExitCode(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'output' => 'Errore durante l\'esecuzione del comando: ' . $e->getMessage(),
                'exit_code' => -1,
            ]);
        }
    }

    public function triggerDraw(Request $request, Player $player)
    {
        try {
            Artisan::call('test:draw', [
                '--player-id' => $player->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comando test:draw eseguito per il player #' . $player->id,
                'output' => Artisan::output(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'esecuzione del comando: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getPlayerContainers(Player $player, DockerContainerService $containerService)
    {
        try {
            $containerController = new ContainerController();
            $payload = $containerController->buildContainerPayloads($player, $containerService);

            return response()->json([
                'success' => true,
                'containers' => $payload,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function setFavoritePlayer(Request $request, Player $player)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utente non autenticato'], 401);
        }

        $user->favorite_player_id = $player->id;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Player preferito impostato']);
    }

    public function unsetFavoritePlayer(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utente non autenticato'], 401);
        }

        $user->favorite_player_id = null;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Player preferito rimosso']);
    }
}