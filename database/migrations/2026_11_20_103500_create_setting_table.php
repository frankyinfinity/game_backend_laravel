<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Setting', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
        });

        // Seed the first entry: "Evolution Score"
        foreach (Setting::DEFAULTS as $key => $value) {
            DB::table('Setting')->insert([
                'key' => $key,
                'value' => $value,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('Setting');
    }
};
