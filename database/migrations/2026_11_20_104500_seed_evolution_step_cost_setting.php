<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        // Seed "Costo per Step di Evoluzione" if not already present
        $exists = DB::table('Setting')->where('key', Setting::KEY_EVOLUTION_STEP_COST)->exists();
        if (!$exists) {
            DB::table('Setting')->insert([
                'key' => Setting::KEY_EVOLUTION_STEP_COST,
                'value' => Setting::DEFAULTS[Setting::KEY_EVOLUTION_STEP_COST],
            ]);
        }
    }

    public function down(): void
    {
        DB::table('Setting')->where('key', Setting::KEY_EVOLUTION_STEP_COST)->delete();
    }
};
