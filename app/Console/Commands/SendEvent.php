<?php

namespace App\Console\Commands;

use App\Events\TestEvent;
use Illuminate\Console\Command;
use Pusher\Pusher;

class SendEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        event(new TestEvent('hello world!'));
        return true;
    }
}
