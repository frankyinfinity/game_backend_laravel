<?php

namespace Tests\Unit;

use App\Jobs\ExecuteBrainScheduleJob;
use App\Models\BrainSchedule;
use App\Observers\BrainScheduleObserver;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class BrainScheduleObserverTest extends TestCase
{
    public function test_created_event_dispatches_brain_job_on_queue(): void
    {
        Bus::fake();

        $brainSchedule = new BrainSchedule();
        $brainSchedule->id = 123;

        $observer = new BrainScheduleObserver();
        $observer->created($brainSchedule);

        Bus::assertDispatched(ExecuteBrainScheduleJob::class, function (ExecuteBrainScheduleJob $job) {
            return $job->queue === 'brain';
        });
    }
}
