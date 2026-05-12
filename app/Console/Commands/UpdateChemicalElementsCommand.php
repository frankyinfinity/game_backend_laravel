<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChimicalElement;
use App\Models\ComplexChimicalElement;
use App\Models\FamilyTile;
use App\Models\FamilyTileLimit;

class UpdateChemicalElementsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chemical:update-values {value=200}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update limit values for all chemical elements and complex chemical elements in FamilyTileLimit table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $value = $this->argument('value');

        $this->info("Updating limit values to {$value} for all FamilyTileLimit records...");

        FamilyTileLimit::query()->update(['limit_value' => $value]);

        $this->info('Update completed.');
    }
}
