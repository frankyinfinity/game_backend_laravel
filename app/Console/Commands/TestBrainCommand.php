<?php

namespace App\Console\Commands;

use App\Models\BrainSchedule;
use App\Models\ElementHasPosition;
use Illuminate\Console\Command;

class TestBrainCommand extends Command
{
    protected $signature = 'test:brain {element_has_position_id=7773}';

    protected $description = 'Create BrainSchedule for an element (no duplicates in state=create)';

    public function handle(): int
    {
        $elementHasPositionId = (int) $this->argument('element_has_position_id');

        $element = ElementHasPosition::query()->find($elementHasPositionId);
        if ($element === null) {
            $this->error("ElementHasPosition {$elementHasPositionId} non trovato.");
            return self::FAILURE;
        }

        $alreadyCreate = BrainSchedule::query()
            ->where('element_has_position_id', $elementHasPositionId)
            ->whereIn('state', [BrainSchedule::STATE_CREATE, BrainSchedule::STATE_IN_PROGRESS])
            ->exists();

        if ($alreadyCreate) {
            $this->info("BrainSchedule gi� presente per ElementHasPosition {$elementHasPositionId}.");
            return self::SUCCESS;
        }

        BrainSchedule::query()->create([
            'element_has_position_id' => $elementHasPositionId,
            'state' => BrainSchedule::STATE_CREATE,
        ]);

        $this->info("BrainSchedule creato per ElementHasPosition {$elementHasPositionId}.");
        return self::SUCCESS;
    }
}
