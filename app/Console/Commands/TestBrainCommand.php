<?php

namespace App\Console\Commands;

use App\Models\ElementHasPosition;
use Illuminate\Console\Command;

class TestBrainCommand extends Command
{
    protected $signature = 'test:brain';

    protected $description = 'Test ElementHasPosition::find(7741)';

    public function handle(): int
    {
        $elementHasPositionId = 7741;
        $item = ElementHasPosition::query()->find($elementHasPositionId);

        if ($item === null) {
            $this->error("ElementHasPosition {$elementHasPositionId} non trovato.");
            return self::FAILURE;
        }

        $this->line(json_encode($item->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return self::SUCCESS;
    }
}
