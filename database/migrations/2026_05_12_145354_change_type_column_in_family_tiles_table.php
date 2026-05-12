<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing data
        DB::table('family_tiles')->where('type', 'solid')->update(['type' => 0]);
        DB::table('family_tiles')->where('type', 'liquid')->update(['type' => 1]);

        Schema::table('family_tiles', function (Blueprint $table) {
            $table->integer('type')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_tiles', function (Blueprint $table) {
            $table->enum('type', ['solid', 'liquid'])->change();
        });

        // Revert data
        DB::table('family_tiles')->where('type', 0)->update(['type' => 'solid']);
        DB::table('family_tiles')->where('type', 1)->update(['type' => 'liquid']);
    }
};
