<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Docker\Docker;

class TestDocker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-docker';

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

        putenv('DOCKER_HOST=tcp://127.0.0.1:2375');
        
        $docker = Docker::create();
        $containers = $docker->containerList(['all' => true]);
        
        $this->table(['ID', 'Image', 'Status'], 
            collect($containers)->map(fn($c) => [
                $c->getId(),
                $c->getImage(),
                $c->getStatus()
            ])
        );

        return 0;

    }
}
