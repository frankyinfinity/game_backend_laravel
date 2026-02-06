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
            "type" => Gene::TYPE_STATIC_RANGE,
            "name" => "Colore Rosso",
            "show_on_registration" => true,
            "min" => 0,
            "max" => 255,
        ]);
        Gene::query()->updateOrCreate(['key' => Gene::KEY_GREEN_TEXTURE], [
            "type" => Gene::TYPE_STATIC_RANGE,
            "name" => "Colore Verde",
            "show_on_registration" => true,
            "min" => 0,
            "max" => 255,
        ]);
        Gene::query()->updateOrCreate(['key' => Gene::KEY_BLUE_TEXTURE], [
            "type" => Gene::TYPE_STATIC_RANGE,
            "name" => "Colore Blu",
            "show_on_registration" => true,
            "min" => 0,
            "max" => 255,
        ]);
        Gene::query()->updateOrCreate(['key' => Gene::KEY_LIFEPOINT], [
            "type" => Gene::DYNAMIC_MAX,
            "name" => "Punti Vita",
            "show_on_registration" => true,
            "min" => 1,
            "max" => null,
            "max_from" => 90,
            "max_to" => 100,
        ]);
        Gene::query()->updateOrCreate(['key' => Gene::KEY_ATTACK], [
            "type" => Gene::DYNAMIC_MAX,
            "name" => "Attacco",
            "show_on_registration" => true,
            "min" => 1,
            "max" => null,
            "max_from" => 45,
            "max_to" => 50,
        ]);
    }
}
