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

        //$channel = 'my-channel';
        //$event = 'my-event';

        /*$pusher = new Pusher(
            'f02185b1bc94c884ce5b',
            'ed669469b28a7ad8317b',
            '1981907',
            [
                'cluster' => 'eu',
                'useTLS' => true,
            ]
        );

        $pusher->trigger($channel, $event, 'funziona');*/

        event(new TestEvent('hello world!'));

        return true;

    }
}
