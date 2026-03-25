<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('docker_volume_name')->nullable()->after('actual_session_id');
        });

        DB::table('players')
            ->whereNull('docker_volume_name')
            ->update([
                'docker_volume_name' => DB::raw("CONCAT('player_', id, '_data')")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('docker_volume_name');
        });
    }
};
