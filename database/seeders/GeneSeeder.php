<?php

namespace Database\Seeders;

use App\Models\Gene;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GeneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Gene::query()->updateOrCreate(['key' => Gene::KEY_RED_TEXTURE], [
            "name" => "Colore Rosso",
            "started" => true,
            "min" => 0,
            "max" => 255,
        ]);
        Gene::query()->updateOrCreate(['key' => Gene::KEY_GREEN_TEXTURE], [
            "name" => "Colore Verde",
            "started" => true,
            "min" => 0,
            "max" => 255,
        ]);
        Gene::query()->updateOrCreate(['key' => Gene::KEY_BLUE_TEXTURE], [
            "name" => "Colore Blu",
            "started" => true,
            "min" => 0,
            "max" => 255,
        ]);
        Gene::query()->updateOrCreate(['key' => Gene::KEY_LIFEPOINT], [
            "name" => "Punti Vita",
            "started" => true,
            "min" => 1,
            "max" => null,
            "max_from" => 90,
            "max_to" => 100,
        ]);
    }
}
