<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\MyEvent;

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
        event(new MyEvent('hello world'));
        return true;
    }
}
