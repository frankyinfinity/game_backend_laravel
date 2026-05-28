<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenRouterModelsCommand extends Command
{
    protected $signature = 'openrouter:models {--all : Mostra anche i modelli non free}';

    protected $description = 'Lista i modelli disponibili da OpenRouter';

    public function handle(): int
    {
        $token = config('services.openrouter.token');

        if (empty($token)) {
            $this->error('OPENROUTER_API_KEY non configurata.');
            return self::FAILURE;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get('https://openrouter.ai/api/v1/models');
        } catch (ConnectionException $exception) {
            $this->error('Impossibile connettersi a OpenRouter.');
            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->error('Errore durante il recupero dei modelli OpenRouter.');
            $this->line($response->body());
            return self::FAILURE;
        }

        $models = collect($response->json('data', []));

        if (! $this->option('all')) {
            $models = $models->filter(fn ($model) => str_contains($model['id'] ?? '', ':free'));
        }

        $rows = $models
            ->values()
            ->map(fn ($model) => [
                $model['id'] ?? '',
                $model['name'] ?? '',
                $model['context_length'] ?? '',
            ])
            ->all();

        if (empty($rows)) {
            $this->warn('Nessun modello trovato.');
            return self::SUCCESS;
        }

        $this->table(['ID', 'Nome', 'Context'], $rows);

        return self::SUCCESS;
    }
}
