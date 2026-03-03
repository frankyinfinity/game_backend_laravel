<?php

namespace App\Observers;

use App\Jobs\ExecuteBrainScheduleJob;
use App\Models\BrainSchedule;

class BrainScheduleObserver
{
    public function created(BrainSchedule $brainSchedule): void
    {
        ExecuteBrainScheduleJob::dispatchSync((int) $brainSchedule->id);
    }
}
