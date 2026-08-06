<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class TestController extends Controller
{
    public function index()
    {
        return view('test');
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
}